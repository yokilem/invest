<?php
// Hata raporlamayı aç
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Session başlat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Veritabanı ayarları
define('DB_HOST', 'localhost');
define('DB_USER', 'u2461468_invest3403-2');
define('DB_PASS', 'AsdAsd587823_?');
define('DB_NAME', 'u2461468_invest3403-1');

// Site ayarları
define('SITE_URL', 'https://invesakprimesyrk.com/');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('CURRENCY', '$');
define('CURRENCY_CODE', 'USD');

// =============================================
// NATRO SMTP AYARLARI - SEÇENEK 1 (SSL)
// =============================================
define('SMTP_HOST', 'mail.kurumsaleposta.com');
define('SMTP_PORT', 465); // SSL için 465
define('SMTP_USERNAME', 'forgot_password@invesakprimesyrk.com'); // KENDİ E-POSTANIZ
define('SMTP_PASSWORD', 'AsdAsd587823_?'); // E-POSTA ŞİFRENİZ
define('SMTP_FROM_EMAIL', 'forgot_password@invesakprimesyrk.com'); 
define('SMTP_FROM_NAME', 'GPU Invest');
define('SMTP_SECURE', 'ssl'); // SSL için

/*
// =============================================
// NATRO SMTP AYARLARI - SEÇENEK 2 (TLS)
// =============================================
define('SMTP_HOST', 'mail.kurumsaleposta.com');
define('SMTP_PORT', 587); // TLS için 587
define('SMTP_USERNAME', 'forgot_password@invesakprimesyrk.com');
define('SMTP_PASSWORD', 'AsdAsd587823_?');
define('SMTP_FROM_EMAIL', 'forgot_password@invesakprimesyrk.com');
define('SMTP_FROM_NAME', 'GPU Invest');
define('SMTP_SECURE', 'tls'); // TLS için

// =============================================
// NATRO SMTP AYARLARI - SEÇENEK 3 (No SSL)
// =============================================
define('SMTP_HOST', 'mail.kurumsaleposta.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'forgot_password@invesakprimesyrk.com');
define('SMTP_PASSWORD', 'AsdAsd587823_?');
define('SMTP_FROM_EMAIL', 'forgot_password@invesakprimesyrk.com');
define('SMTP_FROM_NAME', 'GPU Invest');
define('SMTP_SECURE', ''); // Şifreleme yok
*/

// E-posta doğrulama ayarları
define('EMAIL_VERIFICATION_REQUIRED', false);
define('VERIFICATION_TOKEN_EXPIRE', 24 * 60 * 60);

// Veritabanı bağlantısı
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES utf8");
} catch(PDOException $e) {
    die("❌ Veritabanı bağlantı hatası: " . $e->getMessage());
}

// Fonksiyonları ve dil sistemini yükle
require_once 'functions.php';
require_once 'language.php';

?>