<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/mail_functions.php';

$error = '';
$success = '';

if ($_POST) {
    $email = $_POST['email'];
    
    if (empty($email)) {
        $error = 'Lütfen e-posta adresinizi girin!';
    } else {
        try {
            // E-posta adresini kontrol et
            $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE email = ? AND email_verified = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Reset token oluştur
                $reset_token = generateToken();
                $token_expires = date('Y-m-d H:i:s', time() + 3600); // 1 saat
                
                // Token'ı kaydet
                $update_stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?");
                if ($update_stmt->execute([$reset_token, $token_expires, $user['id']])) {
                    // Şifre sıfırlama e-postası gönder
                    if (sendPasswordResetEmail($user['email'], $user['username'], $reset_token)) {
                        $success = 'Şifre sıfırlama linki e-posta adresinize gönderildi. Lütfen e-postanızı kontrol edin.';
                    } else {
                        $error = 'Şifre sıfırlama e-postası gönderilemedi. Lütfen daha sonra tekrar deneyin.';
                    }
                } else {
                    $error = 'Token kaydedilirken bir hata oluştu!';
                }
            } else {
                $error = 'Bu e-posta adresiyle kayıtlı ve doğrulanmış bir hesap bulunamadı!';
            }
        } catch (Exception $e) {
            error_log("Şifre sıfırlama hatası: " . $e->getMessage());
            $error = 'Şifre sıfırlama sırasında bir hata oluştu!';
        }
    }
}

$page_title = 'Şifremi Unuttum';
?>
<?php include 'includes/header.php'; ?>

<div class="auth-container">
    <div class="auth-form">
        <h2>Şifremi Unuttum</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="email">E-posta Adresiniz:</label>
                <input type="email" id="email" name="email" value="<?php echo $_POST['email'] ?? ''; ?>" required>
                <small>Kayıtlı ve doğrulanmış e-posta adresinizi girin</small>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Şifre Sıfırlama Linki Gönder</button>
        </form>
        
        <p class="auth-link">
            <a href="login.php">← Giriş Sayfasına Dön</a>
        </p>
        
        <p class="auth-link">
            Hesabınız yok mu? <a href="register.php">Kayıt Olun</a>
        </p>
    </div>
</div>

<?php include 'includes/footer.php'; ?>