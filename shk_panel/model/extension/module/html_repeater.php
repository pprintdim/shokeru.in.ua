<?php
class ModelExtensionModuleHtmlRepeater extends Model {

    /* ===============================
       GET ITEMS
    =============================== */
    public function getItems(int $module_id): array {

        $module_id = (int)$module_id;
        if ($module_id <= 0) {
            return [];
        }

        $query = $this->db->query("
            SELECT *
            FROM `" . DB_PREFIX . "html_repeater`
            WHERE module_id = " . (int)$module_id . "
            ORDER BY sort_order ASC, item_id ASC
        ");

        $items = [];

        foreach ($query->rows as $row) {

            $item_id = (int)$row['item_id'];
            $lang_id = (int)$row['language_id'];

            if (!isset($items[$item_id])) {
                $items[$item_id] = [
                    'meta' => [
                        'item_id'    => $item_id,
                        'sort_order' => (int)$row['sort_order'],
                        'status'     => (int)$row['status'],
                        'image'      => $row['image'],
                        'mob_image'  => $row['mob_image'],
                        'link'       => $row['link'] ?? '',
                    ],
                    'lang' => []
                ];
            }

            $items[$item_id]['lang'][$lang_id] = [
                'title'   => $row['title'],
                'content' => $row['content'],
            ];
        }

        return $items;
    }

    /* ===============================
       NEXT ITEM ID
    =============================== */
    public function getNextItemId(int $module_id): int {

        $module_id = (int)$module_id;
        if ($module_id <= 0) {
            return 1;
        }

        $query = $this->db->query("
            SELECT MAX(item_id) AS max_id
            FROM `" . DB_PREFIX . "html_repeater`
            WHERE module_id = " . (int)$module_id
        );

        return ((int)$query->row['max_id']) + 1;
    }

    /* ===============================
       SAVE ITEMS (REPLACE-ALL)
    =============================== */
    public function saveItems(int $module_id, array $items): void {

        $module_id = (int)$module_id;
        if ($module_id <= 0) {
            return;
        }

        // 1️⃣ DELETE ALL ITEMS FOR MODULE
        $this->db->query("
            DELETE FROM `" . DB_PREFIX . "html_repeater`
            WHERE module_id = " . (int)$module_id
        );

        // 2️⃣ INSERT AGAIN
        foreach ($items as $item_id => $item) {

            $item_id = (int)$item_id;
            if ($item_id <= 0) {
                continue;
            }

            $meta = $item['meta'] ?? [];

            $image      = (string)($meta['image'] ?? '');
            $mob_image  = (string)($meta['mob_image'] ?? '');
            $link       = (string)($meta['link'] ?? '');
            $sort_order = (int)($meta['sort_order'] ?? 0);
            $status     = (int)($meta['status'] ?? 1);

            $langs = $item['lang'] ?? [];

            foreach ($langs as $language_id => $lang) {

                $language_id = (int)$language_id;
                if ($language_id <= 0) {
                    continue;
                }

                $title   = (string)($lang['title'] ?? '');
                $content = (string)($lang['content'] ?? '');

                $this->db->query("
                    INSERT INTO `" . DB_PREFIX . "html_repeater`
                    SET
                        module_id   = " . (int)$module_id . ",
                        item_id     = " . (int)$item_id . ",
                        language_id = " . (int)$language_id . ",
                        title       = '" . $this->db->escape($title) . "',
                        content     = '" . $this->db->escape($content) . "',
                        image       = '" . $this->db->escape($image) . "',
                        mob_image   = '" . $this->db->escape($mob_image) . "',
                        link        = '" . $this->db->escape($link) . "',
                        sort_order  = " . (int)$sort_order . ",
                        status      = " . (int)$status
                );
            }
        }
    }
}
