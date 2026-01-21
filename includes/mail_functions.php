<?php
// PHPMailer dosyalarını manuel olarak include ediyoruz
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/SMTP.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function sendEmail($to, $subject, $body, $isHTML = true) {
    // E-posta gönderme özelliği kapalıysa
    if (!defined('SMTP_HOST') || empty(SMTP_HOST)) {
        error_log("SMTP ayarları tanımlı değil. E-posta gönderilemedi: $to");
        return false;
    }
    
    try {
        $mail = new PHPMailer(true);
        
        // SMTP ayarları
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;
        $mail->Timeout = 15; // 15 saniye timeout
        
        // Hata ayıklama (dosyaya yaz)
        $mail->SMTPDebug = 0; // 0 = kapalı, 2 = detaylı debug
        $mail->Debugoutput = 'error_log';
        
        // Gönderen bilgileri
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to);
        
        // İçerik
        $mail->isHTML($isHTML);
        $mail->Subject = $subject;
        $mail->Body = $body;
        
        // Alternatif plain text içerik
        if ($isHTML) {
            $mail->AltBody = strip_tags($body);
        }
        
        // Karakter seti
        $mail->CharSet = 'UTF-8';
        
        $result = $mail->send();
        
        if ($result) {
            error_log("✅ E-posta gönderildi: $to - $subject");
        } else {
            error_log("❌ E-posta gönderilemedi: $to");
        }
        
        return $result;
        
    } catch (Exception $e) {
        error_log("❌ E-posta gönderme hatası (" . SMTP_HOST . ":" . SMTP_PORT . "): " . $e->getMessage());
        return false;
    }
}

// Diğer fonksiyonlar aynı kalacak...
function sendVerificationEmail($email, $username, $token) {
    $verification_url = SITE_URL . '/verify_email.php?token=' . $token;
    
    $subject = 'GPU Invest - E-posta Doğrulama';
    
    $body = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>E-posta Doğrulama</title>
    </head>
    <body>
        <h2>Merhaba $username,</h2>
        <p>GPU Invest hesabınızı doğrulamak için aşağıdaki bağlantıya tıklayın:</p>
        <p><a href='$verification_url'>E-postayı Doğrula</a></p>
        <p>Veya bu linki tarayıcınıza kopyalayın:<br>$verification_url</p>
        <p>Bu link 24 saat boyunca geçerlidir.</p>
        <br>
        <p>Saygılarımızla,<br>GPU Invest Ekibi</p>
    </body>
    </html>
    ";
    
    return sendEmail($email, $subject, $body);
}

function sendPasswordResetEmail($email, $username, $token) {
    $reset_url = SITE_URL . '/reset_password.php?token=' . $token;
    
    $subject = 'GPU Invest - Şifre Sıfırlama';
    
    $body = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Şifre Sıfırlama</title>
    </head>
    <body>
        <h2>Merhaba $username,</h2>
        <p>Şifrenizi sıfırlamak için aşağıdaki bağlantıya tıklayın:</p>
        <p><a href='$reset_url'>Şifremi Sıfırla</a></p>
        <p>Veya bu linki tarayıcınıza kopyalayın:<br>$reset_url</p>
        <p><strong>Önemli:</strong> Bu link 1 saat boyunca geçerlidir.</p>
        <br>
        <p>Saygılarımızla,<br>GPU Invest Ekibi</p>
    </body>
    </html>
    ";
    
    return sendEmail($email, $subject, $body);
}

function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

function isPHPMailerInstalled() {
    $required_files = [
        __DIR__ . '/../vendor/phpmailer/phpmailer/PHPMailer.php',
        __DIR__ . '/../vendor/phpmailer/phpmailer/SMTP.php',
        __DIR__ . '/../vendor/phpmailer/phpmailer/Exception.php'
    ];
    
    foreach ($required_files as $file) {
        if (!file_exists($file)) {
            return false;
        }
    }
    return true;
}
?>