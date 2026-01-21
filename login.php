<?php
// Hata raporlama
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Basit auth kontrolü
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

// Gelişmiş login işlemi
if ($_POST) {
    $username_or_email = $_POST['username_or_email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Debug
    error_log("Login attempt: $username_or_email");
    
    // Kullanıcıyı veritabanında ara (username veya email ile)
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username_or_email, $username_or_email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // E-posta doğrulama kontrolü
            if (defined('EMAIL_VERIFICATION_REQUIRED') && EMAIL_VERIFICATION_REQUIRED && !$user['email_verified']) {
                $error = 'Hesabınızı kullanabilmek için önce e-posta adresinizi doğrulamanız gerekiyor!<br>E-postanızı kontrol edin veya <a href="resend_verification.php" style="color: #3498db;">doğrulama e-postasını yeniden gönderin</a>.';
            } elseif (password_verify($password, $user['password'])) {
                // Giriş başarılı
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['theme'] = $user['theme'];
                
                // Son giriş zamanını güncelle
                $update_stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $update_stmt->execute([$user['id']]);
                
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Şifre hatalı!';
            }
        } else {
            $error = 'Kullanıcı adı veya e-posta bulunamadı!';
        }
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        $error = 'Giriş sırasında bir hata oluştu!';
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap - GPU Invest</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-form {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
        }
        .login-form h2 {
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
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        .btn {
            width: 100%;
            padding: 12px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
        }
        .btn:hover {
            background: #2980b9;
        }
        .demo-accounts {
            margin-top: 1.5rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        .error-message {
            background: #fee;
            color: #c33;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 1rem;
            text-align: center;
        }
        .error-message a {
            color: #3498db;
            text-decoration: none;
        }
        .error-message a:hover {
            text-decoration: underline;
        }
        .auth-link {
            text-align: center;
            margin-top: 1rem;
        }
        .auth-link a {
            color: #3498db;
            text-decoration: none;
        }
        .auth-link a:hover {
            text-decoration: underline;
        }
        .password-reset-link {
            text-align: center;
            margin-top: 0.5rem;
        }
        .password-reset-link a {
            color: #666;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .password-reset-link a:hover {
            color: #3498db;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-form">
        <h2>Giriş Yap</h2>
        
        <?php if (!empty($error)): ?>
            <div class="error-message">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Kullanıcı Adı veya E-posta:</label>
                <input type="text" name="username_or_email" required 
                       placeholder="Kullanıcı adınız veya e-posta adresiniz"
                       value="<?php echo htmlspecialchars($_POST['username_or_email'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Şifre:</label>
                <input type="password" name="password" required 
                       placeholder="Şifreniz">
            </div>
            <button type="submit" class="btn">Giriş Yap</button>
        </form>
        
        <div class="password-reset-link">
            <a href="forgot_password.php">Şifremi Unuttum</a>
        </div>
        
        <div class="auth-link">
            <p>Hesabınız yok mu? <a href="register.php">Kayıt Olun</a></p>
        </div>
        
        <p style="text-align: center; margin-top: 1rem;">
            <a href="index.php">← Ana Sayfaya Dön</a>
        </p>
    </div>
</body>
</html>