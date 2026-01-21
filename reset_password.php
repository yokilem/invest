<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$error = '';
$success = '';
$show_form = false;

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    try {
        // Token'ı kontrol et
        $stmt = $pdo->prepare("SELECT id, reset_token_expires FROM users WHERE reset_token = ?");
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Token süresini kontrol et
            if (strtotime($user['reset_token_expires']) > time()) {
                $show_form = true;
                $user_id = $user['id'];
                
                // Form gönderildiyse
                if ($_POST) {
                    $password = $_POST['password'];
                    $confirm_password = $_POST['confirm_password'];
                    
                    if (empty($password) || empty($confirm_password)) {
                        $error = 'Lütfen tüm alanları doldurun!';
                    } elseif ($password !== $confirm_password) {
                        $error = 'Şifreler eşleşmiyor!';
                    } elseif (strlen($password) < 6) {
                        $error = 'Şifre en az 6 karakter olmalı!';
                    } else {
                        // Şifreyi güncelle
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $update_stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?");
                        
                        if ($update_stmt->execute([$hashed_password, $user_id])) {
                            $success = 'Şifreniz başarıyla sıfırlandı! Şimdi giriş yapabilirsiniz.';
                            $show_form = false;
                        } else {
                            $error = 'Şifre sıfırlama sırasında bir hata oluştu!';
                        }
                    }
                }
            } else {
                $error = 'Şifre sıfırlama linkinin süresi dolmuş. Lütfen yeni bir şifre sıfırlama talebinde bulunun.';
            }
        } else {
            $error = 'Geçersiz veya kullanılmış şifre sıfırlama linki!';
        }
    } catch (Exception $e) {
        error_log("Şifre sıfırlama hatası: " . $e->getMessage());
        $error = 'Şifre sıfırlama sırasında bir hata oluştu!';
    }
} else {
    $error = 'Geçersiz istek!';
}

$page_title = 'Şifre Sıfırlama';
?>
<?php include 'includes/header.php'; ?>

<div class="auth-container">
    <div class="auth-form">
        <h2>Şifre Sıfırlama</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo $error; ?>
                <br><br>
                <a href="forgot_password.php" class="btn btn-secondary">Yeni Şifre Sıfırlama Linki İste</a>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
                <br><br>
                <a href="login.php" class="btn btn-primary">Giriş Yap</a>
            </div>
        <?php endif; ?>
        
        <?php if ($show_form): ?>
            <form method="POST">
                <div class="form-group">
                    <label for="password">Yeni Şifre:</label>
                    <input type="password" id="password" name="password" required minlength="6">
                    <small>Şifre en az 6 karakter olmalıdır</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Yeni Şifre (Tekrar):</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Şifremi Sıfırla</button>
            </form>
        <?php endif; ?>
        
        <p class="auth-link">
            <a href="login.php">← Giriş Sayfasına Dön</a>
        </p>
    </div>
</div>

<?php include 'includes/footer.php'; ?>