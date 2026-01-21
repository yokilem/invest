<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/mail_functions.php';

$error = '';
$success = '';

// GET ile e-posta parametresi geldiyse formda göster
$prefilled_email = $_GET['email'] ?? '';

if ($_POST) {
    $email = $_POST['email'] ?? '';
    
    if (empty($email)) {
        $error = 'Lütfen e-posta adresinizi girin!';
    } else {
        try {
            // Kullanıcıyı ve mevcut doğrulama durumunu kontrol et
            $stmt = $pdo->prepare("SELECT id, username, email, email_verified FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Kullanıcı bilgilerini güvenli şekilde al
                $user_id = $user['id'] ?? 0;
                $username = $user['username'] ?? '';
                $user_email = $user['email'] ?? $email;
                $email_verified = $user['email_verified'] ?? 0;
                
                if ($email_verified) {
                    $error = 'Bu e-posta adresi zaten doğrulanmış! <a href="login.php" style="color: #3498db;">Giriş yapabilirsiniz</a>.';
                } else {
                    // Yeni token oluştur (sınırsız - her zaman gönder)
                    $new_token = generateToken();
                    $token_expires = date('Y-m-d H:i:s', time() + VERIFICATION_TOKEN_EXPIRE);
                    
                    // Token'ı güncelle
                    $update_stmt = $pdo->prepare("UPDATE users SET verification_token = ?, verification_token_expires = ? WHERE id = ?");
                    
                    if ($update_stmt->execute([$new_token, $token_expires, $user_id])) {
                        // Doğrulama e-postasını gönder
                        if (sendVerificationEmail($user_email, $username, $new_token)) {
                            $success = 'Doğrulama e-postası başarıyla gönderildi! Lütfen e-postanızı kontrol edin. (Gelen kutusunu ve spam klasörünü kontrol edin)';
                        } else {
                            $error = 'Doğrulama e-postası gönderilemedi. Lütfen daha sonra tekrar deneyin.';
                        }
                    } else {
                        $error = 'Token güncelleme sırasında bir hata oluştu!';
                    }
                }
            } else {
                $error = 'Bu e-posta adresiyle kayıtlı bir hesap bulunamadı!';
            }
        } catch (Exception $e) {
            error_log("Doğrulama e-postası yeniden gönderme hatası: " . $e->getMessage());
            $error = 'Doğrulama e-postası gönderilirken bir hata oluştu!';
        }
    }
}

$page_title = 'Doğrulama E-postasını Yeniden Gönder';
?>
<?php include 'includes/header.php'; ?>

<div class="auth-container">
    <div class="auth-form">
        <h2>Doğrulama E-postasını Yeniden Gönder</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
                <br><br>
                <a href="login.php" class="btn btn-primary">Giriş Yap</a>
            </div>
        <?php endif; ?>
        
        <?php if (!$success): ?>
            <form method="POST">
                <div class="form-group">
                    <label for="email">E-posta Adresiniz:</label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo htmlspecialchars($prefilled_email ?: ($_POST['email'] ?? '')); ?>" 
                           required placeholder="Kayıtlı e-posta adresiniz">
                    <small>Doğrulama e-postasının gönderileceği e-posta adresinizi girin</small>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Doğrulama E-postasını Gönder</button>
            </form>
            
            
        <?php endif; ?>
        
        <div class="auth-links">
            <p><a href="login.php">← Giriş Sayfasına Dön</a></p>
            <p>Hesabınız yok mu? <a href="register.php">Kayıt Olun</a></p>
            <p>Şifrenizi mi unuttunuz? <a href="forgot_password.php">Şifremi Unuttum</a></p>
        </div>
    </div>
</div>

<style>
.alert {
    padding: 12px 16px;
    border-radius: 6px;
    margin-bottom: 1rem;
    text-align: center;
}

.alert-error {
    background: #fee;
    color: #c33;
    border: 1px solid #fcc;
}

.alert-success {
    background: #efe;
    color: #363;
    border: 1px solid #cfc;
}

.auth-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 20px;
}

.auth-form {
    background: white;
    padding: 2rem;
    border-radius: 10px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    width: 100%;
    max-width: 400px;
}

.auth-form h2 {
    text-align: center;
    margin-bottom: 1.5rem;
    color: #333;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    color: #555;
    font-weight: 600;
}

.form-group input {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 1rem;
    box-sizing: border-box;
}

.form-group input:focus {
    border-color: #3498db;
    outline: none;
    box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
}

.form-group small {
    display: block;
    margin-top: 0.25rem;
    color: #666;
    font-size: 0.8rem;
}

.btn {
    display: inline-block;
    padding: 12px 20px;
    background: #3498db;
    color: white;
    border: none;
    border-radius: 5px;
    font-size: 1rem;
    cursor: pointer;
    text-decoration: none;
    text-align: center;
    transition: background-color 0.3s;
}

.btn:hover {
    background: #2980b9;
}

.btn-primary {
    background: #3498db;
}

.btn-primary:hover {
    background: #2980b9;
}

.btn-secondary {
    background: #95a5a6;
}

.btn-secondary:hover {
    background: #7f8c8d;
}

.btn-block {
    display: block;
    width: 100%;
}

.auth-links {
    text-align: center;
    margin-top: 1.5rem;
}

.auth-links p {
    margin: 0.5rem 0;
}

.auth-links a {
    color: #3498db;
    text-decoration: none;
}

.auth-links a:hover {
    text-decoration: underline;
}

/* Responsive tasarım */
@media (max-width: 480px) {
    .auth-form {
        padding: 1.5rem;
        margin: 10px;
    }
    
    .auth-container {
        padding: 10px;
    }
}
</style>

<?php include 'includes/footer.php'; ?>