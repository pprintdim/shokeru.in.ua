<?php
class ControllerCommonUserPopup extends Controller {

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

        $data['google_client_id'] = (string)$this->config->get('config_google_client_id');
        $data['facebook_app_id']  = (string)$this->config->get('config_facebook_app_id');

        return $this->load->view('common/user_popup', $data);
    }

    public function register() {
        $this->load->language('common/user_popup');
        $json = [];

        if ($this->customer->isLogged()) {
            $json['error'] = $this->language->get('error_logged');
        }

        $this->load->model('account/customer');

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $firstname = trim($this->request->post['firstname'] ?? '');
            $lastname  = trim($this->request->post['lastname'] ?? '');
            $email     = trim($this->request->post['email'] ?? '');
            $telephone = trim($this->request->post['telephone'] ?? '');
            $password  = $this->request->post['password'] ?? '';
            $accept   = $this->request->post['accept'] ?? '';
            $agree     = !empty($this->request->post['agree']);


            if (utf8_strlen($firstname) < 1 || utf8_strlen($firstname) > 32) {
                $json['error']['firstname'] = $this->language->get('error_firstname');
            }

            if (utf8_strlen($lastname) < 1 || utf8_strlen($lastname) > 32) {
                $json['error']['lastname'] = $this->language->get('error_lastname');
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $json['error']['email'] = $this->language->get('error_email_invalid');
            } elseif ($this->model_account_customer->getTotalCustomersByEmail($email)) {
                $json['error']['email'] = $this->language->get('error_email_exists');
            }

            if (utf8_strlen($telephone) < 10 || utf8_strlen($telephone) > 32) {
                $json['error']['telephone'] = $this->language->get('error_telephone');
            }

            if (utf8_strlen($password) < 4 || utf8_strlen($password) > 20) {
                $json['error']['password'] = $this->language->get('error_password');
            }

            if ($password != $accept) {
                $json['error']['accept'] = $this->language->get('error_confirm');
            }

            if (!$agree) {
                $json['error']['agree'] = $this->language->get('error_agree');
            }

            if (!$json) {
                
                $this->model_account_customer->addCustomer([
                    'firstname' => $firstname,
                    'lastname'  => $lastname,
                    'email'     => $email,
                    'telephone' => $telephone,
                'redirect'  => $this->returnUrl(),
                    'password'  => $password,
                    'newsletter'=> 0,
                ]);

                $this->customer->login($email, $password);

                $json['success']  = true;
                $json['redirect'] = $this->url->link('account/account', '', true);
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }



    public function login() {
        $this->load->language('common/user_popup');
        $this->load->model('account/customer');
        $json = [];

        // Якщо користувач вже залогінений
        if ($this->customer->isLogged()) {
            $json['error'] = $this->language->get('error_logged');
        }

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $email    = trim($this->request->post['email'] ?? '');
            $password = $this->request->post['password'] ?? '';

            // Перевірка на порожні поля
            if (!$email || !$password) {
                $json['error'] = $this->language->get('error_login');
            }

            if (!$json) {
                // Перевірка кількості невдалих спроб
                $login_info = $this->model_account_customer->getLoginAttempts($email);

                if ($login_info && $login_info['total'] >= 5) { // можна змінити ліміт
                    $json['error'] = $this->language->get('error_attempts');
                } else {
                    if (!$this->customer->login($email, $password)) {
                        $this->model_account_customer->addLoginAttempt($email);
                        $json['error'] = $this->language->get('error_login');
                    } else {
                        $this->model_account_customer->deleteLoginAttempts($email);
                        $json['success'] = true; // просто успішний статус
                        $json['redirect'] = $this->returnUrl(); // URL редіректу
                    }

                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function googleLogin() {
        $this->load->language('common/user_popup');
        $this->load->model('account/customer');
        $json = [];

        $credential = trim($this->request->post['credential'] ?? '');

        if (!$credential) {
            $json['error'] = 'Помилка авторизації Google';
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        $response = @file_get_contents('https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($credential));
        $payload  = $response ? json_decode($response, true) : null;

        if (!$payload || empty($payload['email'])) {
            $json['error'] = 'Невірний токен Google';
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        $email     = $payload['email'];
        $firstname = $payload['given_name']  ?? '';
        $lastname  = $payload['family_name'] ?? '';

        if (!$this->model_account_customer->getCustomerByEmail($email)) {
            $this->model_account_customer->addCustomer([
                'firstname'  => $firstname ?: $email,
                'lastname'   => $lastname,
                'email'      => $email,
                'telephone'  => '',
                'password'   => token(32),
                'newsletter' => 0,
            ]);
        }

        $this->customer->login($email, '', true);

        $json['success']  = true;
        $json['redirect'] = $this->url->link('account/account', '', true);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function facebookLogin() {
        $this->load->language('common/user_popup');
        $this->load->model('account/customer');
        $json = [];

        $access_token = trim($this->request->post['access_token'] ?? '');

        if (!$access_token) {
            $json['error'] = 'Помилка авторизації Facebook';
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        $response = @file_get_contents('https://graph.facebook.com/me?fields=name,email,first_name,last_name&access_token=' . urlencode($access_token));
        $profile  = $response ? json_decode($response, true) : null;

        if (!$profile || empty($profile['email'])) {
            $json['error'] = 'Не вдалось отримати email з Facebook';
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        $email     = $profile['email'];
        $firstname = $profile['first_name'] ?? '';
        $lastname  = $profile['last_name']  ?? '';

        if (!$this->model_account_customer->getCustomerByEmail($email)) {
            $this->model_account_customer->addCustomer([
                'firstname'  => $firstname ?: $email,
                'lastname'   => $lastname,
                'email'      => $email,
                'telephone'  => '',
                'password'   => token(32),
                'newsletter' => 0,
            ]);
        }

        $this->customer->login($email, '', true);

        $json['success']  = true;
        $json['redirect'] = $this->url->link('account/account', '', true);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /* ===== Passwordless: надіслати код на email ===== */
    public function sendCode() {
        $this->load->language('common/user_popup');
        $this->load->model('account/customer');
        $json = array();

        if ($this->customer->isLogged()) {
            $json['error'] = $this->language->get('error_logged');
        }

        $type      = ($this->request->post['type'] ?? 'register') === 'register' ? 'register' : 'login';
        $email     = trim($this->request->post['email'] ?? '');
        $firstname = trim($this->request->post['firstname'] ?? '');
        $lastname  = trim($this->request->post['lastname'] ?? '');
        $telephone = trim($this->request->post['telephone'] ?? '');
        $agree     = !empty($this->request->post['agree']);

        if (!$json && $type !== 'register') {
            $json['error'] = $this->language->get('error_login_password_required');
        }

        if (!$json) {
            if (!$agree) {
                $json['error'] = $this->language->get('error_agree');
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $json['error'] = $this->language->get('error_email_invalid');
            } else {
                $exists = (bool)$this->model_account_customer->getTotalCustomersByEmail($email);

                if ($type === 'register') {
                    if (utf8_strlen($firstname) < 1 || utf8_strlen($firstname) > 32) {
                        $json['error'] = $this->language->get('error_firstname');
                    } elseif (utf8_strlen($telephone) < 10 || utf8_strlen($telephone) > 32) {
                        $json['error'] = $this->language->get('error_telephone');
                    } elseif ($exists) {
                        $json['error'] = $this->language->get('error_email_exists');
                    }
                }
            }
        }

        // ліміт частоти: не частіше ніж раз на 45с
        if (!$json && !empty($this->session->data['otp']['sent']) && (time() - $this->session->data['otp']['sent']) < 45 && $this->session->data['otp']['email'] === $email) {
            // дозволяємо повторну відправку (resend) — оновлюємо код, але не спамимо
        }

        if (!$json) {
            $code = (string)random_int(100000, 999999);
            $this->session->data['otp'] = array(
                'email'     => $email,
                'code'      => $code,
                'type'      => $type,
                'expires'   => time() + 600,
                'attempts'  => 0,
                'sent'      => time(),
                'firstname' => $firstname,
                'lastname'  => $lastname,
                'telephone' => $telephone,
            );

            $this->sendCodeMail($email, $code);
            $json['success'] = true;
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /* ===== Passwordless: перевірити код + вхід/створення ===== */
    public function verifyCode() {
        $this->load->language('common/user_popup');
        $this->load->model('account/customer');
        $json = array();

        $email = trim($this->request->post['email'] ?? '');
        $code  = trim($this->request->post['code'] ?? '');
        $otp   = $this->session->data['otp'] ?? null;

        if (!$otp || $otp['email'] !== $email) {
            $json['error'] = $this->language->get('error_code_invalid');
        } elseif (time() > $otp['expires']) {
            unset($this->session->data['otp']);
            $json['error'] = $this->language->get('error_code_expired');
        } elseif ($otp['attempts'] >= 5) {
            unset($this->session->data['otp']);
            $json['error'] = $this->language->get('error_attempts');
        } elseif (!hash_equals($otp['code'], $code)) {
            $this->session->data['otp']['attempts']++;
            $json['error'] = $this->language->get('error_code_wrong');
        } else {
            // код вірний
            if ($otp['type'] === 'register' && !$this->model_account_customer->getTotalCustomersByEmail($email)) {
                $password = bin2hex(random_bytes(6));
                $customer_data = array(
                    'firstname'  => $otp['firstname'] ?: $email,
                    'lastname'   => $otp['lastname'] ?: '',
                    'email'      => $email,
                    'telephone'  => $otp['telephone'],
                    'password'   => $password,
                    'auto_generated_password' => $password,
                    'newsletter' => 0,
                );

                $this->model_account_customer->addCustomer($customer_data);
                $this->sendRegisterMail($customer_data);
                $this->storePasswordEnc($email, $password);

                // авто-логін одразу після реєстрації (passwordless)
                $this->customer->login($email, '', true);
                $this->model_account_customer->deleteLoginAttempts($email);

                $msg = $this->language->get('text_register_success');
            } else {
                $json['error'] = $this->language->get('error_login_password_required');
            }

            unset($this->session->data['otp']);

            if (!$json) {
                $json['success']  = true;
                $json['message']  = $msg;
                $json['redirect'] = $otp['redirect'] ?? $this->url->link('account/account', '', true);
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    // Куди повернутись після входу: сторінка, з якої відкрили попап (поле redirect, напр. відгук
    // на товарі) — лише свій домен; інакше кабінет.
    private function returnUrl() {
        $target = isset($this->request->post['redirect']) ? trim((string)$this->request->post['redirect']) : '';

        if ($target !== '' && preg_match('#^https?://#i', $target)) {
            foreach (array($this->config->get('config_url'), $this->config->get('config_ssl')) as $base) {
                if ($base && strpos($target, (string)$base) === 0) {
                    return $target;
                }
            }
        }

        return $this->url->link('account/account', '', true);
    }

    private function sendCodeMail($email, $code) {
        $subject = sprintf($this->language->get('text_code_email_subject'), $this->config->get('config_name'));
        $body    = sprintf($this->language->get('text_code_email_body'), $code);

        $mail = new Mail($this->config->get('config_mail_engine'));
        $mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
        $mail->smtp_username = $this->config->get('config_mail_smtp_username');
        $mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
        $mail->smtp_port     = $this->config->get('config_mail_smtp_port');
        $mail->smtp_timeout  = $this->config->get('config_mail_smtp_timeout');
        $mail->setTo($email);
        $mail->setFrom($this->config->get('config_email'));
        $mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
        $mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
        $mail->setHtml('<div style="font-family:Arial,sans-serif;font-size:15px;color:#202222">' . $body . '</div>');
        $mail->send();
    }

    private function sendRegisterMail($customer_data) {
        $this->load->language('mail/register');

        $data['text_welcome'] = sprintf($this->language->get('text_welcome'), html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
        $data['text_login'] = $this->language->get('text_login');
        $data['text_password'] = $this->language->get('text_password');
        $data['text_approval'] = $this->language->get('text_approval');
        $data['text_service'] = $this->language->get('text_service');
        $data['text_thanks'] = $this->language->get('text_thanks');

        $this->load->model('account/customer_group');

        $customer_group_id = $customer_data['customer_group_id'] ?? $this->config->get('config_customer_group_id');
        $customer_group_info = $this->model_account_customer_group->getCustomerGroup($customer_group_id);

        $data['approval'] = $customer_group_info ? $customer_group_info['approval'] : '';
        $data['login'] = $this->url->link('account/login', '', true);
        $data['store'] = html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8');
        $data['password'] = $customer_data['auto_generated_password'];

        $mail = new Mail($this->config->get('config_mail_engine'));
        $mail->parameter = $this->config->get('config_mail_parameter');
        $mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
        $mail->smtp_username = $this->config->get('config_mail_smtp_username');
        $mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
        $mail->smtp_port = $this->config->get('config_mail_smtp_port');
        $mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');
        $mail->setTo($customer_data['email']);
        $mail->setFrom($this->config->get('config_email'));
        $mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
        $mail->setSubject(sprintf($this->language->get('text_subject'), html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8')));
        $mail->setText($this->load->view('mail/register', $data));
        $mail->send();
    }

    /* ===== Відновлення пароля: новий пароль на email ===== */
    public function forgotten() {
        $this->load->language('common/user_popup');
        $this->load->model('account/customer');
        $json = array();

        if ($this->customer->isLogged()) {
            $json['error'] = $this->language->get('error_logged');
        }

        $email = trim($this->request->post['email'] ?? '');

        if (!$json) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $json['error'] = $this->language->get('error_email_invalid');
            } elseif (!$this->model_account_customer->getTotalCustomersByEmail($email)) {
                $json['error'] = $this->language->get('error_no_account');
            } else {
                $password = bin2hex(random_bytes(6));
                $this->model_account_customer->editPassword($email, $password);
                $this->storePasswordEnc($email, $password);
                $this->sendForgottenMail($email, $password);
                $json['success'] = true;
                $json['message'] = $this->language->get('text_forgot_sent');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /* зберегти пароль оборотно (показ власнику в кабінеті) */
    private function storePasswordEnc($email, $password) {
        require_once(DIR_SYSTEM . 'library/passvault.php');
        $enc = PassVault::enc($password, (string)$this->config->get('config_encryption'));
        $this->db->query("UPDATE `" . DB_PREFIX . "customer` SET password_enc = '" . $this->db->escape($enc) . "' WHERE email = '" . $this->db->escape($email) . "'");
    }

    private function sendForgottenMail($email, $password) {
        $subject = sprintf($this->language->get('text_forgot_email_subject'), html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
        $body    = sprintf($this->language->get('text_forgot_email_body'), $password);

        $mail = new Mail($this->config->get('config_mail_engine'));
        $mail->parameter = $this->config->get('config_mail_parameter');
        $mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
        $mail->smtp_username = $this->config->get('config_mail_smtp_username');
        $mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
        $mail->smtp_port     = $this->config->get('config_mail_smtp_port');
        $mail->smtp_timeout  = $this->config->get('config_mail_smtp_timeout');
        $mail->setTo($email);
        $mail->setFrom($this->config->get('config_email'));
        $mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
        $mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
        $mail->setHtml('<div style="font-family:Arial,sans-serif;font-size:15px;color:#202222">' . $body . '</div>');
        $mail->send();
    }

}
