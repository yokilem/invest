<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Eğer zaten giriş yapmış ve admin ise admin panele yönlendir
if (isLoggedIn() && isAdmin()) {
    redirect('index.php');
}

// Eğer giriş yapmış ama admin değilse dashboard'a yönlendir
if (isLoggedIn() && !isAdmin()) {
    redirect('../dashboard.php');
}

$error = '';
if ($_POST) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // Debug için
    error_log("Login attempt: " . $username);
    
    // Admin giriş kontrolü
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND role = 'admin'");
    $stmt->execute([$username, $username]);
    $admin = $stmt->fetch();
    
    if ($admin) {
        error_log("Admin found: " . $admin['username']);
        error_log("Stored hash: " . $admin['password']);
        error_log("Input password: " . $password);
        
        // Şifre kontrolü
        if (password_verify($password, $admin['password'])) {
            error_log("Password verified successfully");
            
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['username'] = $admin['username'];
            $_SESSION['email'] = $admin['email'];
            $_SESSION['role'] = $admin['role'];
            $_SESSION['theme'] = $admin['theme'];
            
            redirect('index.php');
        } else {
            error_log("Password verification failed");
            $error = 'Şifre hatalı!';
        }
    } else {
        error_log("Admin not found for: " . $username);
        $error = 'Admin kullanıcı adı bulunamadı!';
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Giriş - GPU Invest</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .admin-login-form {
            background: white;
            padding: 3rem;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        
        .admin-login-form h2 {
            color: #333;
            margin-bottom: 1.5rem;
            font-size: 1.8rem;
            font-weight: 600;
        }
        
        .admin-logo {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #667eea;
        }
        
        .admin-login-form .form-group {
            margin-bottom: 1.5rem;
            text-align: left;
        }
        
        .admin-login-form label {
            display: block;
            margin-bottom: 0.5rem;
            color: #555;
            font-weight: 500;
        }
        
        .admin-login-form input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            font-family: inherit;
        }
        
        .admin-login-form input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .admin-login-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: inherit;
        }
        
        .admin-login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .error-message {
            background: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 1rem;
            border: 1px solid #fcc;
            font-weight: 500;
        }
        
        .back-to-site {
            margin-top: 1.5rem;
            color: #666;
        }
        
        .back-to-site a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .back-to-site a:hover {
            color: #764ba2;
            text-decoration: underline;
        }
        
        .demo-info {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-top: 1.5rem;
            border-left: 4px solid #667eea;
        }
        
        .demo-info h4 {
            margin: 0 0 0.5rem 0;
            color: #333;
            font-size: 1rem;
        }
        
        .demo-info p {
            margin: 0.25rem 0;
            color: #666;
            font-size: 0.9rem;
        }
        
        .demo-info strong {
            color: #333;
        }
    </style>
</head>
<body>
    <div class="admin-login-container">
        <div class="admin-login-form">
            <div class="admin-logo">⚡</div>
            <h2>Admin Giriş Paneli</h2>
            
            <?php if ($error): ?>
                <div class="error-message">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="username">Kullanıcı Adı:</label>
                    <input type="text" id="username" name="username" required placeholder="Admin kullanıcı adınız" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Şifre:</label>
                    <input type="password" id="password" name="password" required placeholder="Admin şifreniz">
                </div>
                
                <button type="submit" class="admin-login-btn">Admin Girişi Yap</button>
            </form>
            
            
            
            <div class="back-to-site">
                <a href="../index.php">← Siteye Dön</a>
            </div>
        </div>
    </div>
</body>
</html>