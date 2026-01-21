<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$page_title = __("settings");
$success = '';
$error = '';

// Kullanıcı bilgilerini getir
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Tema değiştirme
if (isset($_POST['theme'])) {
    $theme = $_POST['theme'];
    if (updateUserTheme($user_id, $theme)) {
        $_SESSION['theme'] = $theme;
        $success = __('theme_updated_success');
    } else {
        $error = __('theme_update_error');
    }
}

// Şifre değiştirme
if (isset($_POST['current_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (!password_verify($current_password, $user['password'])) {
        $error = __('current_password_incorrect');
    } elseif ($new_password !== $confirm_password) {
        $error = __('passwords_do_not_match');
    } elseif (strlen($new_password) < 6) {
        $error = __('password_min_length');
    } else {
        $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        if ($stmt->execute([$hashedPassword, $user_id])) {
            $success = __('password_updated_success');
        } else {
            $error = __('password_update_error');
        }
    }
}

// Profil bilgilerini güncelleme
if (isset($_POST['username'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    
    // Kullanıcı adı kontrolü
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $stmt->execute([$username, $user_id]);
    if ($stmt->rowCount() > 0) {
        $error = __('username_already_exists');
    } else {
        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
        if ($stmt->execute([$username, $email, $user_id])) {
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;
            $success = __('profile_updated_success');
        } else {
            $error = __('profile_update_error');
        }
    }
}
?>
<?php include 'includes/header.php'; ?>

<div class="page-header">
    <h1><?php echo __("account_settings"); ?></h1>
    <p><?php echo __('manage_account_settings'); ?></p>
</div>

<div class="content-row">
    <div class="content-col">
        <!-- Tema Ayarları -->
        <div class="content-card">
            <h3><?php echo __('theme_settings'); ?></h3>
            
            <?php if ($success && isset($_POST['theme'])): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="theme-selection">
                    <div class="theme-option">
                        <input type="radio" id="light-theme" name="theme" value="light" 
                               <?php echo getCurrentTheme() == 'light' ? 'checked' : ''; ?>>
                        <label for="light-theme" class="theme-label">
                            <div class="theme-preview light-theme">
                                <div class="preview-header"></div>
                                <div class="preview-content">
                                    <div class="preview-item"></div>
                                    <div class="preview-item"></div>
                                </div>
                            </div>
                            <span><?php echo __('light_theme'); ?></span>
                        </label>
                    </div>
                    
                    <div class="theme-option">
                        <input type="radio" id="dark-theme" name="theme" value="dark" 
                               <?php echo getCurrentTheme() == 'dark' ? 'checked' : ''; ?>>
                        <label for="dark-theme" class="theme-label">
                            <div class="theme-preview dark-theme">
                                <div class="preview-header"></div>
                                <div class="preview-content">
                                    <div class="preview-item"></div>
                                    <div class="preview-item"></div>
                                </div>
                            </div>
                            <span><?php echo __('dark_theme'); ?></span>
                        </label>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><?php echo __('apply_theme'); ?></button>
                </div>
            </form>
        </div>
        
        <!-- Şifre Değiştir -->
        <div class="content-card">
            <h3><?php echo __('change_password'); ?></h3>
            
            <?php if ($success && isset($_POST['current_password'])): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($error && isset($_POST['current_password'])): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="current_password"><?php echo __('current_password'); ?>:</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                
                <div class="form-group">
                    <label for="new_password"><?php echo __('new_password'); ?>:</label>
                    <input type="password" id="new_password" name="new_password" required minlength="6">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password"><?php echo __('confirm_password'); ?>:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><?php echo __('update_password'); ?></button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="content-col">
        <!-- Profil Bilgileri -->
        <div class="content-card">
            <h3><?php echo __('profile_information'); ?></h3>
            
            <?php if ($success && isset($_POST['username'])): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($error && isset($_POST['username'])): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="username"><?php echo __('username'); ?>:</label>
                    <input type="text" id="username" name="username" required 
                           value="<?php echo htmlspecialchars($user['username']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="email"><?php echo __('email'); ?>:</label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo htmlspecialchars($user['email']); ?>">
                </div>
                
                <div class="user-info">
                    <div class="info-item">
                        <span class="info-label"><?php echo __('account_type'); ?>:</span>
                        <span class="info-value"><?php echo $user['role'] == 'admin' ? __('administrator') : __('user'); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label"><?php echo __('registration_date'); ?>:</span>
                        <span class="info-value"><?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label"><?php echo __('last_login'); ?>:</span>
                        <span class="info-value"><?php echo date('d.m.Y H:i'); ?></span>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><?php echo __('update_information'); ?></button>
                </div>
            </form>
        </div>
        
        <!-- Hesap İstatistikleri -->
        <div class="content-card">
            <h3><?php echo __('account_statistics'); ?></h3>
            
            <?php
            // GPU sayısı
            $gpu_count = $pdo->prepare("SELECT COUNT(*) FROM user_gpus WHERE user_id = ?");
            $gpu_count->execute([$user_id]);
            $total_gpus = $gpu_count->fetchColumn();
            
            // Toplam yatırım
            $total_investment = $pdo->prepare("SELECT SUM(amount) FROM investments WHERE user_id = ? AND status = 'approved'");
            $total_investment->execute([$user_id]);
            $investment_amount = $total_investment->fetchColumn() ?: 0;
            
            // Toplam kazanç
            $total_earnings = $pdo->prepare("
                SELECT SUM(g.monthly_income) 
                FROM user_gpus ug 
                JOIN gpus g ON ug.gpu_id = g.id 
                WHERE ug.user_id = ?
            ");
            $total_earnings->execute([$user_id]);
            $monthly_earnings = $total_earnings->fetchColumn() ?: 0;
            ?>
            
            <div class="stats-grid mini">
                <div class="stat-card mini">
                    <div class="stat-icon">🖥️</div>
                    <div class="stat-info">
                        <h3><?php echo __('gpu_count'); ?></h3>
                        <p class="stat-value"><?php echo $total_gpus; ?></p>
                    </div>
                </div>
                
                <div class="stat-card mini">
                    <div class="stat-icon">💰</div>
                    <div class="stat-info">
                        <h3><?php echo __('total_investment'); ?></h3>
                        <p class="stat-value"><?php echo formatPrice($investment_amount); ?></p>
                    </div>
                </div>
                
                <div class="stat-card mini">
                    <div class="stat-icon">📈</div>
                    <div class="stat-info">
                        <h3><?php echo __('monthly_earnings'); ?></h3>
                        <p class="stat-value"><?php echo formatPrice($monthly_earnings); ?></p>
                    </div>
                </div>
                
                <div class="stat-card mini">
                    <div class="stat-icon">🏆</div>
                    <div class="stat-info">
                        <h3><?php echo __('level'); ?></h3>
                        <p class="stat-value">
                            <?php 
                            if ($total_gpus >= 10) echo __('elite');
                            elseif ($total_gpus >= 5) echo __('professional');
                            elseif ($total_gpus >= 2) echo __('intermediate');
                            else echo __('beginner');
                            ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Dil Ayarları -->
        <div class="content-card">
            <h3><?php echo __('language_settings'); ?></h3>
            <p><?php echo __('change_website_language'); ?>:</p>
            
            <div class="language-selection">
                <a href="?lang=tr" class="language-option <?php echo $_SESSION['language'] == 'tr' ? 'active' : ''; ?>">
                    <span>🇹🇷 <?php echo __('turkish'); ?></span>
                </a>
                <a href="?lang=en" class="language-option <?php echo $_SESSION['language'] == 'en' ? 'active' : ''; ?>">
                    <span>🇺🇸 <?php echo __('english'); ?></span>
                </a>
                <a href="?lang=ru" class="language-option <?php echo $_SESSION['language'] == 'ru' ? 'active' : ''; ?>">
                    <span>🇷🇺 <?php echo __('russian'); ?></span>
                </a>
                <a href="?lang=uz" class="language-option <?php echo $_SESSION['language'] == 'uz' ? 'active' : ''; ?>">
                    <span>🇺🇿 <?php echo __('uzbek'); ?></span>
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.theme-selection {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.theme-option input[type="radio"] {
    display: none;
}

.theme-label {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 1rem;
    border: 2px solid var(--border-color);
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.theme-option input[type="radio"]:checked + .theme-label {
    border-color: var(--primary-color);
    background-color: rgba(52, 152, 219, 0.1);
}

.theme-preview {
    width: 100%;
    height: 80px;
    border-radius: 6px;
    margin-bottom: 0.5rem;
    overflow: hidden;
}

.light-theme {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
}

.light-theme .preview-header {
    height: 20px;
    background: #3498db;
}

.light-theme .preview-content {
    padding: 10px;
}

.light-theme .preview-item {
    height: 8px;
    background: #6c757d;
    margin-bottom: 5px;
    border-radius: 2px;
}

.light-theme .preview-item:last-child {
    width: 70%;
}

.dark-theme {
    background: #2c3e50;
    border: 1px solid #34495e;
}

.dark-theme .preview-header {
    height: 20px;
    background: #2980b9;
}

.dark-theme .preview-content {
    padding: 10px;
}

.dark-theme .preview-item {
    height: 8px;
    background: #7f8c8d;
    margin-bottom: 5px;
    border-radius: 2px;
}

.dark-theme .preview-item:last-child {
    width: 70%;
}

.user-info {
    background: var(--bg-color);
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
}

.info-item {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--border-color);
}

.info-item:last-child {
    border-bottom: none;
}

.info-label {
    color: var(--text-color);
    opacity: 0.8;
}

.info-value {
    font-weight: 600;
    color: var(--text-color);
}

.stats-grid.mini {
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.stat-card.mini {
    padding: 1rem;
    text-align: center;
}

.stat-card.mini .stat-icon {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
}

.stat-card.mini .stat-info h3 {
    font-size: 0.8rem;
    margin-bottom: 0.25rem;
}

.stat-card.mini .stat-value {
    font-size: 1rem;
}

.language-selection {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.5rem;
}

.language-option {
    display: block;
    padding: 0.75rem;
    text-align: center;
    background: var(--bg-color);
    border: 2px solid var(--border-color);
    border-radius: 8px;
    text-decoration: none;
    color: var(--text-color);
    transition: all 0.3s ease;
}

.language-option:hover {
    border-color: var(--primary-color);
}

.language-option.active {
    border-color: var(--primary-color);
    background-color: rgba(52, 152, 219, 0.1);
    font-weight: 600;
}
</style>

<?php include 'includes/footer.php'; ?>