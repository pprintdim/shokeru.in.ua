<?php
class ControllerExtensionModuleSlideshow extends Controller {

    private function parseBannerText($text) {

        // force string
        $text = (string)$text;

        // decode html
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');

        // wrap [ ... ] into <span>
        $text = preg_replace_callback(
            '/\[([^\]]+)\]/u',
            function ($m) {
                return '<span>' . trim($m[1]) . '</span>';
            },
            $text
        );

        // keep new lines
        return nl2br($text);
    }

    // Порожнє посилання, «#» і «/» (головна) → каталог: «Детальніше» не має вести на ту саму сторінку
    private function bannerLink($link) {
        $link = trim((string)$link);
        $base = rtrim((string)$this->config->get('config_url'), '/') . '/';
        $base_ssl = rtrim((string)$this->config->get('config_ssl'), '/') . '/';

        if ($link === '' || $link === '#' || $link === '/' || $link === $base || $link === $base_ssl || $link === rtrim($base, '/') || $link === rtrim($base_ssl, '/')) {
            return $this->url->link('product/shop');
        }

        return $link;
    }

    // Чи має MP4 звукову доріжку: у moov шукаємо hdlr із типом «soun». Результат кешується за шляхом і mtime.
    private function hasAudio($src) {
        $src = (string)$src;

        if ($src === '' || preg_match('~^(https?:)?//~i', $src)) {
            return false;
        }

        $file = strpos($src, 'image/') === 0 ? DIR_IMAGE . substr($src, 6) : DIR_APPLICATION . '../' . ltrim($src, '/');

        if (!is_file($file)) {
            return false;
        }

        $key = 'shokeru.hero.audio.' . md5($file . filemtime($file));
        $cached = $this->cache->get($key);

        if ($cached !== false && $cached !== null) {
            return (bool)$cached;
        }

        $audio = (bool)preg_match('~hdlr.{8}soun~s', (string)file_get_contents($file));

        $this->cache->set($key, $audio ? 1 : 0);

        return $audio;
    }

    public function index($setting) {
        static $module = 0;

        $this->load->model('design/banner');
        $this->load->model('tool/image');

        // один H1 на сторінку: на головній заголовок банера — H1, на каталозі/акціях — h2 (там свій H1)
        $route = isset($this->request->get['route']) ? $this->request->get['route'] : 'common/home';
        $data['hero_tag'] = ($route === 'common/home' || $route === '') ? 'h1' : 'h2';

        $data['banners'] = [];

        if (empty($setting['banner_id'])) {
            return '';
        }

        $results = $this->model_design_banner->getBanner($setting['banner_id']);

        foreach ($results as $result) {

            $type = !empty($result['type']) ? $result['type'] : 'image';

            if ($type === 'video') {
                // потрібне відео ПК
                if (empty($result['video']) || !is_file(DIR_IMAGE . $result['video'])) {
                    continue;
                }

                $desktop = 'image/' . $result['video'];
                $mob     = (!empty($result['mob_video']) && is_file(DIR_IMAGE . $result['mob_video']))
                    ? 'image/' . $result['mob_video']
                    : $desktop; // фолбек на десктопне відео

                // постери (картинки) — поки відео вантажиться
                $poster     = (!empty($result['image']) && is_file(DIR_IMAGE . $result['image'])) ? $this->model_tool_image->webp($result['image']) : '';
                $mob_poster = (!empty($result['mob_image']) && is_file(DIR_IMAGE . $result['mob_image'])) ? $this->model_tool_image->webp($result['mob_image']) : $poster;

                $data['banners'][] = [
                    'title'        => $this->parseBannerText($result['title']),
                    'link'         => $this->bannerLink($result['link']),
                    'description'  => $this->parseBannerText($result['description']),
                    'is_video'     => true,
                    'is_video_mob' => true,
                    'audio'        => $this->hasAudio($desktop),
                    'audio_mob'    => $this->hasAudio($mob),
                    'poster'       => $poster,
                    'mob_poster'   => $mob_poster,
                    'images' => [
                        'desktop'   => $desktop,
                        'tablet'    => $desktop,
                        'mob_image' => $mob,
                    ],
                ];
            } else {
                // картинка
                if (empty($result['image']) || !is_file(DIR_IMAGE . $result['image'])) {
                    continue;
                }

                $has_mob = !empty($result['mob_image']) && is_file(DIR_IMAGE . $result['mob_image']);
                $mob_src = $has_mob ? $result['mob_image'] : $result['image'];

                $data['banners'][] = [
                    'title'        => $this->parseBannerText($result['title']),
                    'link'         => $this->bannerLink($result['link']),
                    'description'  => $this->parseBannerText($result['description']),
                    'is_video'     => false,
                    'is_video_mob' => false,
                    'audio'        => false,
                    'audio_mob'    => false,
                    'poster'       => '',
                    'mob_poster'   => '',
                    'images' => [
                        'desktop'   => $this->model_tool_image->webp($result['image']),
                        'tablet'    => $this->model_tool_image->resize($result['image'], (int)$setting['width'], (int)$setting['height']),
                        'mob_image' => $this->model_tool_image->webp($mob_src),
                    ],
                ];
            }
        }

        $data['module'] = $module++;

        // кнопка «Звук»: показується лише якщо в слайдері є відео зі звуковою доріжкою
        $data['has_sound'] = false;

        foreach ($data['banners'] as $banner) {
            if (!empty($banner['audio']) || !empty($banner['audio_mob'])) {
                $data['has_sound'] = true;
            }
        }

        $this->load->language('extension/module/slideshow');
        $data['text_sound_on'] = $this->language->get('text_sound_on');
        $data['text_sound_off'] = $this->language->get('text_sound_off');

        $data['catalog_url'] = $this->url->link('product/shop');

        // тексти під кнопками (моб) — з налаштувань модуля, fallback на мовний ключ
        $lang_id = (int)$this->config->get('config_language_id');
        $data['note_title'] = isset($setting['note'][$lang_id]['title']) ? html_entity_decode($setting['note'][$lang_id]['title'], ENT_QUOTES, 'UTF-8') : '';
        $data['note_text']  = isset($setting['note'][$lang_id]['text'])  ? html_entity_decode($setting['note'][$lang_id]['text'], ENT_QUOTES, 'UTF-8')  : $this->language->get('text_hero_note');

        // переваги: кожен рядок textarea — окремий пункт
        $data['note_benefits'] = array();
        if (!empty($setting['note'][$lang_id]['benefits'])) {
            foreach (explode("\n", html_entity_decode($setting['note'][$lang_id]['benefits'], ENT_QUOTES, 'UTF-8')) as $line) {
                $line = trim($line);
                if ($line !== '') {
                    $data['note_benefits'][] = $line;
                }
            }
        }

        return $this->load->view('extension/module/slideshow', $data);
    }
}
