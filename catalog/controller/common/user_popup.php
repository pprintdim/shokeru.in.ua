<?php
/**
 * Вхід і реєстрація — лише попапом з одноразовим кодом на email (паролів немає):
 * index()      — розмітка попапа (common/footer, на кожній сторінці);
 * sendCode()   — JSON: 6-значний код на email (type=login — існуючий клієнт, type=register — спершу валідація форми);
 *                код живе в сесії 10 хв, повтор — через 55 с, 5 хибних спроб убивають код;
 * verifyCode() — JSON: перевіряє код, створює клієнта (random-пароль) і логінить; повертає redirect.
 * Контракт відповіді: {success, message, redirect} або {error: "<текст>", errors: {"<поле>": "<текст>"}}.
 * Сторінок /login /register /forgotten немає: account/* робить 301 на головну з ?auth=.
 */
class ControllerCommonUserPopup extends Controller {
    const RESEND_AFTER = 55;
    const CODE_TTL = 600;
    const MAX_ATTEMPTS = 5;

    public function index() {
        // Завантажуємо мову
        $this->load->language('common/user_popup');

        $data['name'] = $this->config->get('config_name');

        $checkout_id = $this->config->get('config_checkout_id');
        $account_id  = $this->config->get('config_account_id');

        $link1 = '';
        $link2 = '';

        $this->load->model('catalog/information');

        if ($checkout_id) {
            $info = $this->model_catalog_information->getInformation($checkout_id);

            if ($info) {
                $link1 = '<a href="' . $this->url->link(
                    'information/information/agree',
                    'information_id=' . $checkout_id,
                    true
                ) . '" class="agree">' . $info['title'] . '</a>';
            }
        }

        if ($account_id) {
            $info = $this->model_catalog_information->getInformation($account_id);

            if ($info) {
                $link2 = '<a href="' . $this->url->link(
                    'information/information/agree',
                    'information_id=' . $account_id,
                    true
                ) . '" class="agree">' . $info['title'] . '</a>';
            }
        }

        if ($link1 || $link2) {
            $data['text_agree'] = sprintf(
                $this->language->get('text_agree'),
                $link1,
                $link2
            );
        } else {
            $data['text_agree'] = '';
        }

        $server = $this->request->server['HTTPS'] ? $this->config->get('config_ssl') : $this->config->get('config_url');

        $data['logo'] = is_file(DIR_IMAGE . $this->config->get('config_logo')) 
            ? $server . 'image/' . $this->config->get('config_logo') 
            : '';

        $data['email'] = $this->config->get('config_email');
		$data['instagram'] = $this->config->get('config_instagram');
		$data['facebook'] = $this->config->get('config_facebook');
        $data['phones'] = [];

        $phones = [
            $this->config->get('config_telephone'),
            $this->config->get('config_fax'),
        ];

        foreach ($phones as $phone) {
            $phone = trim((string)$phone);
            if ($phone === '') continue;

            $data['phones'][] = [
                'href'  => 'tel:' . preg_replace('/\D+/', '', $phone),
                'label' => $phone,
            ];
        }

        $data['home'] = $this->url->link('common/home');
        $data['send_url'] = $this->url->link('common/user_popup/sendCode', '', true);
        $data['verify_url'] = $this->url->link('common/user_popup/verifyCode', '', true);

        // 301 з account/login|register приводить на головну з ?auth=login|register — js/features.js відкриває попап
        $auto = isset($this->request->get['auth']) ? (string)$this->request->get['auth'] : '';

        $data['auto'] = in_array($auto, array('login', 'register')) ? $auto : '';

        return $this->load->view('common/user_popup', $data);
    }


    public function sendCode() {
        $this->load->language('common/user_popup');
        $this->load->model('account/customer');

        $json = array();

        if ($this->customer->isLogged()) {
            $json['error']['warning'] = $this->language->get('error_logged');
        }

        $type = (isset($this->request->post['type']) && $this->request->post['type'] == 'register') ? 'register' : 'login';
        $email = isset($this->request->post['email']) ? trim((string)$this->request->post['email']) : '';
        $data = array();

        if (!$json && ((utf8_strlen($email) > 96) || !filter_var($email, FILTER_VALIDATE_EMAIL))) {
            $json['error']['email'] = $this->language->get('error_email_invalid');
        }

        if (!$json && $type == 'register') {
            $firstname = isset($this->request->post['firstname']) ? trim((string)$this->request->post['firstname']) : '';
            $lastname = isset($this->request->post['lastname']) ? trim((string)$this->request->post['lastname']) : '';
            $telephone = isset($this->request->post['telephone']) ? trim((string)$this->request->post['telephone']) : '';

            // той самий формат, що й у решті магазину (startup/phone.php → Phone::normalize)
            $this->load->library('phone');
            $telephone = Phone::normalize($telephone);

            if ((utf8_strlen($firstname) < 1) || (utf8_strlen($firstname) > 32)) {
                $json['error']['firstname'] = $this->language->get('error_firstname');
            }

            if ((utf8_strlen($lastname) > 32)) {
                $json['error']['lastname'] = $this->language->get('error_lastname');
            }

            if ((utf8_strlen($telephone) < 10) || (utf8_strlen($telephone) > 32)) {
                $json['error']['telephone'] = $this->language->get('error_telephone');
            }

            if (empty($this->request->post['agree'])) {
                $json['error']['agree'] = $this->language->get('error_agree');
            }

            if (!$json && $this->model_account_customer->getTotalCustomersByEmail($email)) {
                $json['error']['email'] = $this->language->get('error_email_exists');
            }

            $data = array(
                'firstname' => $firstname,
                'lastname'  => $lastname,
                'telephone' => $telephone
            );
        }

        if (!$json && $type == 'login') {
            $customer_info = $this->model_account_customer->getCustomerByEmail($email);

            if (!$customer_info) {
                $json['error']['email'] = $this->language->get('error_no_account');
            } elseif (!$customer_info['status']) {
                $json['error']['email'] = $this->language->get('error_approved');
            }
        }

        // повторна відправка на ту саму адресу в тій самій сесії — не частіше ніж раз на RESEND_AFTER секунд
        if (!$json && isset($this->session->data['otp']) && $this->session->data['otp']['email'] == $email && (time() - $this->session->data['otp']['sent']) < self::RESEND_AFTER) {
            $json['error']['email'] = $this->language->get('error_too_often');
        }

        if (!$json) {
            $code = (string)random_int(100000, 999999);

            $this->session->data['otp'] = array(
                'email'    => $email,
                'code'     => $code,
                'type'     => $type,
                'data'     => $data,
                'redirect' => $this->returnUrl(),
                'expires'  => time() + self::CODE_TTL,
                'attempts' => 0,
                'sent'     => time()
            );

            $this->sendCodeMail($email, $code);

            $json['success'] = true;
            $json['message'] = $this->language->get('text_code_sent');
            $json['resend_after'] = self::RESEND_AFTER;
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($this->formJson($json)));
    }

    public function verifyCode() {
        $this->load->language('common/user_popup');
        $this->load->model('account/customer');

        $json = array();

        $email = isset($this->request->post['email']) ? trim((string)$this->request->post['email']) : '';
        $code = isset($this->request->post['code']) ? preg_replace('/\D+/', '', (string)$this->request->post['code']) : '';
        $otp = isset($this->session->data['otp']) ? $this->session->data['otp'] : false;

        if ($this->customer->isLogged()) {
            $json['error']['warning'] = $this->language->get('error_logged');
        } elseif (strlen($code) != 6) {
            $json['error']['code'] = $this->language->get('error_code_required');
        } elseif (!$otp || $otp['email'] != $email) {
            $json['error']['code'] = $this->language->get('error_code_invalid');
        } elseif (time() > $otp['expires']) {
            unset($this->session->data['otp']);
            $json['error']['code'] = $this->language->get('error_code_expired');
        } elseif ($otp['attempts'] >= self::MAX_ATTEMPTS) {
            unset($this->session->data['otp']);
            $json['error']['code'] = $this->language->get('error_attempts');
        } elseif (!hash_equals((string)$otp['code'], $code)) {
            $this->session->data['otp']['attempts']++;
            $json['error']['code'] = $this->language->get('error_code_wrong');
        }

        if (!$json) {
            if ($otp['type'] == 'register') {
                if ($this->model_account_customer->getTotalCustomersByEmail($email)) {
                    $json['error']['email'] = $this->language->get('error_email_exists');
                } else {
                    $this->model_account_customer->addCustomer(array(
                        'customer_group_id' => (int)$this->config->get('config_customer_group_id'),
                        'firstname'         => $otp['data']['firstname'] ?: $email,
                        'lastname'          => $otp['data']['lastname'],
                        'email'             => $email,
                        'telephone'         => $otp['data']['telephone'],
                        'password'          => token(20),
                        'newsletter'        => 0
                    ));

                    $this->model_account_customer->deleteLoginAttempts($email);
                }
            }

            // група клієнтів може вимагати підтвердження: вхід по override приймає лише status = 1
            if (!$json && !$this->customer->login($email, '', true)) {
                $json['error']['warning'] = $this->language->get('error_approved');
            }

            if (!$json) {
                unset($this->session->data['otp']);
                unset($this->session->data['guest']);

                $this->afterLogin();

                $json['success'] = true;
                $json['message'] = ($otp['type'] == 'register') ? $this->language->get('text_register_success') : $this->language->get('text_login_success');
                $json['redirect'] = !empty($otp['redirect']) ? $otp['redirect'] : $this->url->link('account/account', '', true);
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($this->formJson($json)));
    }

    // що робив account/login після входу: адреси за замовчуванням + гостьовий список обраного
    private function afterLogin() {
        $this->load->model('account/address');

        if ($this->config->get('config_tax_customer') == 'payment') {
            $this->session->data['payment_address'] = $this->model_account_address->getAddress($this->customer->getAddressId());
        }

        if ($this->config->get('config_tax_customer') == 'shipping') {
            $this->session->data['shipping_address'] = $this->model_account_address->getAddress($this->customer->getAddressId());
        }

        if (isset($this->session->data['wishlist']) && is_array($this->session->data['wishlist'])) {
            $this->load->model('account/wishlist');

            foreach ($this->session->data['wishlist'] as $key => $product_id) {
                $this->model_account_wishlist->addWishlist($product_id);

                unset($this->session->data['wishlist'][$key]);
            }
        }
    }

    /**
     * Куди повернутись після входу: сторінка, з якої відкрили попап (поле redirect), або адреса, яку зберіг
     * account/login перед редіректом на головну (session redirect) — лише свій домен; інакше кабінет.
     */
    private function returnUrl() {
        $targets = array(
            'checkout' => 'checkout/checkout',
            'wishlist' => 'account/wishlist',
            'account'  => 'account/account'
        );

        $target = isset($this->request->post['redirect']) ? trim((string)$this->request->post['redirect']) : '';

        if (isset($targets[$target])) {
            unset($this->session->data['redirect']);

            return $this->url->link($targets[$target], '', true);
        }

        if ($target !== '' && preg_match('#^https?://#i', $target) && $this->isOwnUrl($target)) {
            unset($this->session->data['redirect']);

            return $target;
        }

        if (isset($this->session->data['redirect'])) {
            $redirect = str_replace('&amp;', '&', (string)$this->session->data['redirect']);

            unset($this->session->data['redirect']);

            if ($this->isOwnUrl($redirect) && $redirect != str_replace('&amp;', '&', $this->url->link('account/logout', '', true))) {
                return $redirect;
            }
        }

        return $this->url->link('account/account', '', true);
    }

    private function isOwnUrl($url) {
        foreach (array($this->config->get('config_url'), $this->config->get('config_ssl')) as $base) {
            if ($base && strpos($url, (string)$base) === 0) {
                return true;
            }
        }

        return false;
    }

    private function sendCodeMail($email, $code) {
        $store = html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8');

        $mail = new Mail($this->config->get('config_mail_engine'));
        $mail->parameter = $this->config->get('config_mail_parameter');
        $mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
        $mail->smtp_username = $this->config->get('config_mail_smtp_username');
        $mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
        $mail->smtp_port = $this->config->get('config_mail_smtp_port');
        $mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');
        $mail->setTo($email);
        $mail->setFrom($this->config->get('config_email'));
        $mail->setSender($store);
        $mail->setSubject(html_entity_decode(sprintf($this->language->get('text_code_email_subject'), $store), ENT_QUOTES, 'UTF-8'));
        $mail->setHtml('<div style="font-family:Arial,sans-serif;font-size:15px;color:#202222">' . sprintf($this->language->get('text_code_email_body'), $code) . '</div>');
        $mail->send();
    }

    // {error: {...}} -> {error: "<перше>", errors: {...}} (ajax-контракт теми)
    private function formJson($json) {
        if (!empty($json['error']) && is_array($json['error'])) {
            $errors = $json['error'];

            $json['error'] = (string)reset($errors);
            $json['errors'] = $errors;
        }

        return $json;
    }
}
