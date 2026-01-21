<?php
// Dil dosyası yolu - SESSION kontrolü ekle
if (!isset($_SESSION['language'])) {
    $_SESSION['language'] = 'tr'; // Varsayılan dil
}

$language_file = __DIR__ . '/languages/' . $_SESSION['language'] . '.php';

// Varsayılan dil Türkçe
if (!file_exists($language_file)) {
    $language_file = __DIR__ . '/languages/tr.php';
    $_SESSION['language'] = 'tr';
}

// Dil dosyasını yükle
require_once $language_file;

// Dil değiştirme
if (isset($_GET['lang'])) {
    $lang = $_GET['lang'];
    $allowed_langs = ['tr', 'en', 'ru', 'uz'];
    if (in_array($lang, $allowed_langs)) {
        $_SESSION['language'] = $lang;
        // Mevcut sayfaya geri dön (lang parametresini kaldırarak)
        $current_url = strtok($_SERVER['REQUEST_URI'], '?');
        header('Location: ' . $current_url);
        exit;
    }
}

// Dil fonksiyonu
function __($key) {
    global $lang;
    return isset($lang[$key]) ? $lang[$key] : $key;
}
?>