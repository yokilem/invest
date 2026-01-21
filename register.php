<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/mail_functions.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';
$success = '';

if ($_POST) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validasyon
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Tüm alanları doldurun!';
    } elseif ($password !== $confirm_password) {
        $error = 'Şifreler eşleşmiyor!';
    } elseif (strlen($password) < 6) {
        $error = 'Şifre en az 6 karakter olmalı!';
    } else {
        try {
            // Kullanıcı adı ve e-posta kontrolü
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if ($stmt->fetch()) {
                $error = 'Bu kullanıcı adı veya e-posta zaten kullanılıyor!';
            } else {
                // Token oluştur
                $verification_token = generateToken();
                $token_expires = date('Y-m-d H:i:s', time() + VERIFICATION_TOKEN_EXPIRE);
                
                // Kullanıcıyı oluştur (doğrulanmamış olarak)
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, verification_token, verification_token_expires, email_verified) VALUES (?, ?, ?, ?, ?, 0)");
                
                if ($stmt->execute([$username, $email, $hashed_password, $verification_token, $token_expires])) {
                    // Doğrulama e-postası gönder
                    if (sendVerificationEmail($email, $username, $verification_token)) {
                        $success = 'Kayıt başarılı! E-posta adresinize doğrulama linki gönderildi. Lütfen e-postanızı kontrol edin.';
                    } else {
                        $error = 'Kayıt başarılı ancak doğrulama e-postası gönderilemedi. Lütfen destek ile iletişime geçin.';
                    }
                } else {
                    $error = 'Kayıt sırasında bir hata oluştu!';
                }
            }
        } catch (Exception $e) {
            error_log("Kayıt hatası: " . $e->getMessage());
            $error = 'Kayıt sırasında bir hata oluştu!';
        }
    }
}

$page_title = __('register');
?>
<?php include 'includes/header.php'; ?>

<div class="auth-container">
    <div class="auth-form">
        <h2><?php echo __('register'); ?></h2>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username"><?php echo __('username'); ?>:</label>
                <input type="text" id="username" name="username" value="<?php echo $_POST['username'] ?? ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email"><?php echo __('email'); ?>:</label>
                <input type="email" id="email" name="email" value="<?php echo $_POST['email'] ?? ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password"><?php echo __('password'); ?>:</label>
                <input type="password" id="password" name="password" required minlength="6">
                <small>Şifre en az 6 karakter olmalıdır</small>
            </div>
            
            <div class="form-group">
                <label for="confirm_password"><?php echo __('confirm_password'); ?>:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block"><?php echo __('register'); ?></button>
        </form>
        
        <p class="auth-link">
            <?php echo __('already_have_account'); ?> <a href="login.php"><?php echo __('login'); ?></a>
        </p>
        
        <p class="auth-link">
            <a href="forgot_password.php">Şifremi Unuttum</a>
        </p>
    </div>
</div>

<?php include 'includes/footer.php'; ?>