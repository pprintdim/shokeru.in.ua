<?php
// Guard: не робити fatal, якщо файл english/ відсутній (наприклад, не залитий на прод)
$__ui_lang = DIR_LANGUAGE . 'english/module/universal_import.php';
if (is_file($__ui_lang)) {
    require_once($__ui_lang);
}
