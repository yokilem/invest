[file name]: admin/settings.php
[file content begin]
<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// ADMIN GİRİŞ KONTROLÜ
if (!isLoggedIn()) {
    redirect('login.php');
}

if (!isAdmin()) {
    redirect('../dashboard.php');
}

$page_title = "Sistem Ayarları";
$admin_page = true;

// Ayarlar tablosunu kontrol et ve oluştur
try {
    $pdo->query("SELECT 1 FROM site_settings LIMIT 1");
} catch (Exception $e) {
    // Tablo yoksa oluştur
    $pdo->exec("
        CREATE TABLE site_settings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    
    // Varsayılan ayarları ekle
    $default_settings = [
        'site_name' => 'GPU Invest',
        'site_description' => 'Yüksek performanslı GPU yatırım platformu',
        'currency' => '$',
        'currency_code' => 'USD',
        'min_investment' => '100',
        'min_withdrawal' => '50',
        'withdrawal_fee' => '2',
        'support_email' => 'destek@gpuinvest.com',
        'maintenance_mode' => '0',
        'user_registration' => '1',
        'email_verification' => '0',
        'session_timeout' => '60',
        'force_ssl' => '0',
        'login_attempts' => '1',
        'password_strength' => 'medium',
        'site_logo' => ''
    ];
    
    foreach ($default_settings as $key => $value) {
        $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)");
        $stmt->execute([$key, $value]);
    }
}

// getSetting() fonksiyonu artık includes/functions.php'den geliyor - BURAYI SİLDİK

// Logo yükleme fonksiyonu
function uploadLogo($file) {
    $target_dir = __DIR__ . '/../assets/images/';
    
    // Klasörü oluştur
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $file_name = 'logo.' . $file_extension;
    $target_file = $target_dir . $file_name;
    
    // Dosya tipi kontrolü
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'svg'];
    if (!in_array($file_extension, $allowed_types)) {
        return ['success' => false, 'error' => 'Sadece JPG, JPEG, PNG, GIF ve SVG dosyaları yükleyebilirsiniz.'];
    }
    
    // Dosya boyutu kontrolü (2MB)
    if ($file['size'] > 2 * 1024 * 1024) {
        return ['success' => false, 'error' => 'Dosya boyutu 2MB\'dan küçük olmalıdır.'];
    }
    
    // Eski logo varsa sil
    $old_files = glob($target_dir . 'logo.*');
    foreach ($old_files as $old_file) {
        if (is_file($old_file)) {
            unlink($old_file);
        }
    }
    
    // Dosya yükleme
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        // Web erişimi için yol
        $web_path = 'assets/images/' . $file_name;
        return ['success' => true, 'file_path' => $web_path];
    }
    
    return ['success' => false, 'error' => 'Logo yüklenirken hata oluştu.'];
}

// Banner yükleme işlemi
if ($_POST && isset($_POST['upload_banner'])) {
    if (isset($_FILES['banner']) && $_FILES['banner']['error'] === UPLOAD_ERR_OK) {
        $upload_result = uploadBanner($_FILES['banner']);
        
        if ($upload_result['success']) {
            $stmt = $pdo->prepare("
                INSERT INTO site_settings (setting_key, setting_value) 
                VALUES ('home_banner', ?) 
                ON DUPLICATE KEY UPDATE setting_value = ?
            ");
            $stmt->execute([$upload_result['file_path'], $upload_result['file_path']]);
            $success = "Banner başarıyla yüklendi!";
        } else {
            $error = $upload_result['error'];
        }
    } else {
        $error = "Lütfen bir banner dosyası seçin!";
    }
}

// Banner silme işlemi
if (isset($_GET['delete_banner'])) {
    $banner_path = getSetting('home_banner');
    if ($banner_path && file_exists('../' . $banner_path)) {
        unlink('../' . $banner_path);
    }
    $stmt = $pdo->prepare("DELETE FROM site_settings WHERE setting_key = 'home_banner'");
    $stmt->execute();
    $success = "Banner başarıyla silindi!";
    header("Location: settings.php");
    exit;
}

// Özellik görseli yükleme işlemi
if ($_POST && isset($_POST['upload_features'])) {
    $uploaded_count = 0;
    
    foreach (['passive_income', 'high_commission', 'secure_investment'] as $feature) {
        if (isset($_FILES['feature_' . $feature]) && $_FILES['feature_' . $feature]['error'] === UPLOAD_ERR_OK) {
            $upload_result = uploadFeatureImage($_FILES['feature_' . $feature], $feature);
            
            if ($upload_result['success']) {
                $stmt = $pdo->prepare("
                    INSERT INTO site_settings (setting_key, setting_value) 
                    VALUES ('feature_$feature', ?) 
                    ON DUPLICATE KEY UPDATE setting_value = ?
                ");
                $stmt->execute([$upload_result['file_path'], $upload_result['file_path']]);
                $uploaded_count++;
            }
        }
    }
    
    if ($uploaded_count > 0) {
        $success = "$uploaded_count özellik görseli başarıyla yüklendi!";
    }
}

// Özellik görseli silme işlemi
if (isset($_GET['delete_feature'])) {
    $feature_key = $_GET['delete_feature'];
    $feature_path = getSetting('feature_' . $feature_key);
    
    if ($feature_path && file_exists('../' . $feature_path)) {
        unlink('../' . $feature_path);
    }
    
    $stmt = $pdo->prepare("DELETE FROM site_settings WHERE setting_key = 'feature_$feature_key'");
    $stmt->execute();
    
    $success = "Özellik görseli başarıyla silindi!";
    header("Location: settings.php");
    exit;
}

// Banner yükleme fonksiyonu
function uploadBanner($file) {
    $target_dir = __DIR__ . '/../assets/images/banners/';
    
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $file_name = 'home_banner.' . $file_extension;
    $target_file = $target_dir . $file_name;
    
    $allowed_types = ['jpg', 'jpeg', 'png'];
    if (!in_array($file_extension, $allowed_types)) {
        return ['success' => false, 'error' => 'Sadece JPG, JPEG ve PNG dosyaları yükleyebilirsiniz.'];
    }
    
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'error' => 'Dosya boyutu 5MB\'dan küçük olmalıdır.'];
    }
    
    // Eski banner varsa sil
    $old_files = glob($target_dir . 'home_banner.*');
    foreach ($old_files as $old_file) {
        if (is_file($old_file)) {
            unlink($old_file);
        }
    }
    
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        $web_path = 'assets/images/banners/' . $file_name;
        return ['success' => true, 'file_path' => $web_path];
    }
    
    return ['success' => false, 'error' => 'Banner yüklenirken hata oluştu.'];
}

// Özellik görseli yükleme fonksiyonu
function uploadFeatureImage($file, $feature_name) {
    $target_dir = __DIR__ . '/../assets/images/features/';
    
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $file_name = $feature_name . '.' . $file_extension;
    $target_file = $target_dir . $file_name;
    
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'svg'];
    if (!in_array($file_extension, $allowed_types)) {
        return ['success' => false, 'error' => 'Sadece JPG, JPEG, PNG, GIF ve SVG dosyaları yükleyebilirsiniz.'];
    }
    
    if ($file['size'] > 2 * 1024 * 1024) {
        return ['success' => false, 'error' => 'Dosya boyutu 2MB\'dan küçük olmalıdır.'];
    }
    
    // Eski feature görseli varsa sil
    $old_files = glob($target_dir . $feature_name . '.*');
    foreach ($old_files as $old_file) {
        if (is_file($old_file)) {
            unlink($old_file);
        }
    }
    
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        $web_path = 'assets/images/features/' . $file_name;
        return ['success' => true, 'file_path' => $web_path];
    }
    
    return ['success' => false, 'error' => 'Görsel yüklenirken hata oluştu.'];
}

// Ayarları kaydetme
if ($_POST && isset($_POST['save_settings'])) {
    $settings_to_update = [
        'site_name' => $_POST['site_name'],
        'site_description' => $_POST['site_description'],
        'currency' => $_POST['currency'],
        'currency_code' => getCurrencyCode($_POST['currency']),
        'min_investment' => floatval($_POST['min_investment']),
        'min_withdrawal' => floatval($_POST['min_withdrawal']),
        'withdrawal_fee' => floatval($_POST['withdrawal_fee']),
        'support_email' => $_POST['support_email'],
        'maintenance_mode' => isset($_POST['maintenance_mode']) ? '1' : '0',
        'user_registration' => isset($_POST['user_registration']) ? '1' : '0',
        'email_verification' => isset($_POST['email_verification']) ? '1' : '0',
        'session_timeout' => intval($_POST['session_timeout']),
        'force_ssl' => isset($_POST['force_ssl']) ? '1' : '0',
        'login_attempts' => isset($_POST['login_attempts']) ? '1' : '0',
        'password_strength' => $_POST['password_strength']
    ];
    
    try {
        $pdo->beginTransaction();
        
        foreach ($settings_to_update as $key => $value) {
            $stmt = $pdo->prepare("
                INSERT INTO site_settings (setting_key, setting_value) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE setting_value = ?
            ");
            $stmt->execute([$key, $value, $value]);
        }
        
        $pdo->commit();
        $success = "Ayarlar başarıyla kaydedildi!";
        
        // Session'da para birimini güncelle
        $_SESSION['currency'] = $settings_to_update['currency'];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Ayarlar kaydedilirken hata oluştu: " . $e->getMessage();
    }
}

// Logo yükleme işlemi
if ($_POST && isset($_POST['upload_logo'])) {
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $upload_result = uploadLogo($_FILES['logo']);
        
        if ($upload_result['success']) {
            // Logo yolunu veritabanına kaydet
            $stmt = $pdo->prepare("
                INSERT INTO site_settings (setting_key, setting_value) 
                VALUES ('site_logo', ?) 
                ON DUPLICATE KEY UPDATE setting_value = ?
            ");
            $stmt->execute([$upload_result['file_path'], $upload_result['file_path']]);
            
            $success = "Logo başarıyla yüklendi!";
        } else {
            $error = $upload_result['error'];
        }
    } else {
        $error = "Lütfen bir logo dosyası seçin!";
    }
}

// Logo silme işlemi
if (isset($_GET['delete_logo'])) {
    $logo_path = getSetting('site_logo');
    
    if ($logo_path && file_exists('../' . $logo_path)) {
        unlink('../' . $logo_path);
    }
    
    // Veritabanından logo kaydını sil
    $stmt = $pdo->prepare("DELETE FROM site_settings WHERE setting_key = 'site_logo'");
    $stmt->execute();
    
    $success = "Logo başarıyla silindi!";
    
    header("Location: settings.php");
    exit;
}

// Para birimi kodunu belirleme fonksiyonu
function getCurrencyCode($currency_symbol) {
    $currency_map = [
        '$' => 'USD',
        '₺' => 'TRY', 
        '€' => 'EUR',
        '£' => 'GBP'
    ];
    return $currency_map[$currency_symbol] ?? 'USD';
}

// Mevcut ayarları getir
$current_settings = [
    'site_name' => getSetting('site_name', 'GPU Invest'),
    'site_description' => getSetting('site_description', 'Yüksek performanslı GPU yatırım platformu'),
    'currency' => getSetting('currency', '$'),
    'min_investment' => getSetting('min_investment', '100'),
    'min_withdrawal' => getSetting('min_withdrawal', '50'),
    'withdrawal_fee' => getSetting('withdrawal_fee', '2'),
    'support_email' => getSetting('support_email', 'destek@gpuinvest.com'),
    'maintenance_mode' => getSetting('maintenance_mode', '0'),
    'user_registration' => getSetting('user_registration', '1'),
    'email_verification' => getSetting('email_verification', '0'),
    'session_timeout' => getSetting('session_timeout', '60'),
    'force_ssl' => getSetting('force_ssl', '0'),
    'login_attempts' => getSetting('login_attempts', '1'),
    'password_strength' => getSetting('password_strength', 'medium'),
    'site_logo' => getSetting('site_logo', '')
];

?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['language']; ?>" data-theme="<?php echo getCurrentTheme(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .settings-info {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #3498db;
        }
        .currency-preview {
            display: inline-block;
            margin-left: 10px;
            padding: 4px 8px;
            background: #27ae60;
            color: white;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        .logo-upload-area {
            border: 2px dashed #ccc;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            margin-bottom: 20px;
            cursor: pointer;
            transition: border-color 0.3s;
            background: #f8f9fa;
        }
        .logo-upload-area:hover {
            border-color: #3498db;
            background: #e7f3ff;
        }
        .logo-upload-area.dragover {
            border-color: #3498db;
            background: #d1ecf1;
        }
        .current-logo {
            text-align: center;
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .current-logo img {
            max-width: 200px;
            max-height: 100px;
            object-fit: contain;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 10px;
            background: white;
        }
        .logo-preview {
            max-width: 200px;
            max-height: 100px;
            object-fit: contain;
            margin-top: 10px;
            display: none;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 10px;
            background: white;
        }
        .logo-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
            justify-content: center;
        }
		
		
		.features-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
}

.feature-image-item {
    padding: 15px;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    background: #f8f9fa;
}

.feature-image-item h4 {
    margin: 0 0 10px 0;
    color: #2c3e50;
}

.current-feature-image {
    text-align: center;
    margin: 10px 0;
}

.feature-upload {
    background: #fff;
    border: 2px dashed #dee2e6;
}

.feature-upload:hover {
    border-color: #3498db;
    background: #f8f9fa;
}
    </style>
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>⚡ Admin Panel</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="index.php" class="nav-item">
                    <span class="nav-icon">📊</span>
                    <span class="nav-text">Dashboard</span>
                </a>
                <a href="users.php" class="nav-item">
                    <span class="nav-icon">👥</span>
                    <span class="nav-text">Kullanıcılar</span>
                </a>
                <a href="gpus.php" class="nav-item">
                    <span class="nav-icon">🖥️</span>
                    <span class="nav-text">Ekran Kartları</span>
                </a>
                <a href="investments.php" class="nav-item">
                    <span class="nav-icon">💰</span>
                    <span class="nav-text">Yatırımlar</span>
                </a>
                <a href="commissions.php" class="nav-item">
                    <span class="nav-icon">📈</span>
                    <span class="nav-text">Komisyonlar</span>
                </a>
                <a href="payment_methods.php" class="nav-item">
                    <span class="nav-icon">💳</span>
                    <span class="nav-text">Ödeme Yöntemleri</span>
                </a>
                <a href="settings.php" class="nav-item active">
                    <span class="nav-icon">⚙️</span>
                    <span class="nav-text">Ayarlar</span>
                </a>
                <a href="../dashboard.php" class="nav-item">
                    <span class="nav-icon">🏠</span>
                    <span class="nav-text">Siteye Dön</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="admin-main">
            <header class="admin-header">
                <h1>Sistem Ayarları</h1>
                <div class="admin-actions">
                    <a href="../includes/logout.php" class="btn btn-sm btn-danger">Çıkış Yap</a>
                </div>
            </header>

            <div class="admin-content">
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="settings-info">
                    <h4>⚙️ Sistem Ayarları</h4>
                    <p>Bu sayfadan sitenizin genel ayarlarını yönetebilirsiniz. Yaptığınız değişiklikler anında uygulanacaktır.</p>
                </div>

                <form method="POST" enctype="multipart/form-data">
                    <div class="content-row">
                        <div class="content-col">
                            <!-- Logo Ayarları -->
                            <div class="content-card">
                                <h3>�️ Logo Ayarları</h3>
                                
                                <!-- Mevcut Logo -->
                                <?php if ($current_settings['site_logo'] && file_exists('../' . $current_settings['site_logo'])): ?>
                                <div class="current-logo">
                                    <h4>Mevcut Logo:</h4>
                                    <img src="../<?php echo $current_settings['site_logo']; ?>" alt="Site Logo" 
                                         onerror="this.style.display='none'">
                                    <div class="logo-actions">
                                        <a href="?delete_logo=1" class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Logoyu silmek istediğinizden emin misiniz?')">
                                            Logoyu Sil
                                        </a>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- Logo Yükleme -->
                                <div class="logo-upload-area" id="logoUploadArea">
                                    <div id="logoUploadText">
                                        <h4>📁 Logo Yükle</h4>
                                        <p>Logoyu sürükleyip bırakın veya tıklayarak seçin</p>
                                        <small>JPG, PNG, GIF, SVG formatında, maksimum 2MB</small>
                                    </div>
                                    <input type="file" id="logo" name="logo" accept="image/*" style="display: none;" 
                                           onchange="previewLogo(this)">
                                    <img id="logoPreview" class="logo-preview" alt="Logo önizleme">
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" name="upload_logo" class="btn btn-primary">Logoyu Yükle</button>
                                </div>
                            </div>
							
							
							<!-- Banner Ayarları -->
<div class="content-card">
    <h3>🎯 Ana Sayfa Banner</h3>
    
    <!-- Mevcut Banner -->
    <?php 
    $current_banner = getSetting('home_banner', '');
    if ($current_banner && file_exists('../' . $current_banner)): 
    ?>
    <div class="current-logo">
        <h4>Mevcut Banner:</h4>
        <img src="../<?php echo $current_banner; ?>" alt="Ana Sayfa Banner" 
             style="max-width: 100%; max-height: 300px; object-fit: cover;"
             onerror="this.style.display='none'">
        <div class="logo-actions">
            <a href="?delete_banner=1" class="btn btn-sm btn-danger" 
               onclick="return confirm('Bannerı silmek istediğinizden emin misiniz?')">
                Bannerı Sil
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Banner Yükleme -->
    <div class="logo-upload-area" id="bannerUploadArea">
        <div id="bannerUploadText">
            <h4>📁 Banner Yükle</h4>
            <p>Banner görselini sürükleyip bırakın veya tıklayarak seçin</p>
            <small>Önerilen boyut: 1200x400 piksel. JPG, PNG formatında, maksimum 5MB</small>
        </div>
        <input type="file" id="banner" name="banner" accept="image/*" style="display: none;" 
               onchange="previewBanner(this)">
        <img id="bannerPreview" class="logo-preview" alt="Banner önizleme" style="max-width: 100%; max-height: 200px; object-fit: cover;">
    </div>
    
    <div class="form-actions">
        <button type="submit" name="upload_banner" class="btn btn-primary">Bannerı Yükle</button>
    </div>
</div>

<!-- Özellik Görselleri -->
<div class="content-card">
    <h3>⭐ Özellik Görselleri</h3>
    <p class="text-muted">Ana sayfadaki özellik kartları için görseller yükleyin.</p>
    
    <div class="features-grid">
        <?php 
        $features = [
            'passive_income' => ['title' => 'Pasif Gelir', 'desc' => 'passive_income_desc'],
            'high_commission' => ['title' => 'Yüksek Komisyon', 'desc' => 'high_commission_desc'],
            'secure_investment' => ['title' => 'Güvenli Yatırım', 'desc' => 'secure_investment_desc']
        ];
        
        foreach ($features as $key => $feature): 
            $current_feature_image = getSetting('feature_' . $key, '');
        ?>
        <div class="feature-image-item">
            <h4><?php echo $feature['title']; ?></h4>
            
            <?php if ($current_feature_image && file_exists('../' . $current_feature_image)): ?>
            <div class="current-feature-image">
                <img src="../<?php echo $current_feature_image; ?>" alt="<?php echo $feature['title']; ?>" 
                     style="max-width: 150px; max-height: 100px; object-fit: cover; border-radius: 8px;">
                <div class="logo-actions">
                    <a href="?delete_feature=<?php echo $key; ?>" class="btn btn-sm btn-danger" 
                       onclick="return confirm('<?php echo $feature['title']; ?> görselini silmek istediğinizden emin misiniz?')">
                        Sil
                    </a>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="logo-upload-area feature-upload" style="padding: 15px; margin: 10px 0;">
                <input type="file" id="feature_<?php echo $key; ?>" name="feature_<?php echo $key; ?>" 
                       accept="image/*" style="display: none;" 
                       onchange="previewFeature(this, '<?php echo $key; ?>')">
                <div onclick="document.getElementById('feature_<?php echo $key; ?>').click()" style="cursor: pointer;">
                    <p>📁 <?php echo $feature['title']; ?> Görseli</p>
                    <small>Önerilen boyut: 200x150 piksel</small>
                </div>
                <img id="featurePreview_<?php echo $key; ?>" class="logo-preview" 
                     alt="<?php echo $feature['title']; ?> önizleme" 
                     style="max-width: 150px; max-height: 100px; object-fit: cover; border-radius: 8px;">
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <div class="form-actions">
        <button type="submit" name="upload_features" class="btn btn-primary">Özellik Görsellerini Yükle</button>
    </div>
</div>

                            <!-- Genel Ayarlar -->
                            <div class="content-card">
                                <h3>Genel Ayarlar</h3>
                                
                                <div class="form-group">
                                    <label for="site_name">Site Adı:</label>
                                    <input type="text" id="site_name" name="site_name" 
                                           value="<?php echo htmlspecialchars($current_settings['site_name']); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="site_description">Site Açıklaması:</label>
                                    <textarea id="site_description" name="site_description" rows="3"><?php echo htmlspecialchars($current_settings['site_description']); ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label for="support_email">Destek E-posta:</label>
                                    <input type="email" id="support_email" name="support_email" 
                                           value="<?php echo htmlspecialchars($current_settings['support_email']); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="currency">Para Birimi:</label>
                                    <select id="currency" name="currency" onchange="updateCurrencyPreview()">
                                        <option value="$" <?php echo $current_settings['currency'] == '$' ? 'selected' : ''; ?>>Dolar ($)</option>
                                        <option value="₺" <?php echo $current_settings['currency'] == '₺' ? 'selected' : ''; ?>>Türk Lirası (₺)</option>
                                        <option value="€" <?php echo $current_settings['currency'] == '€' ? 'selected' : ''; ?>>Euro (€)</option>
                                        <option value="£" <?php echo $current_settings['currency'] == '£' ? 'selected' : ''; ?>>İngiliz Sterlini (£)</option>
                                    </select>
                                    <span class="currency-preview" id="currencyPreview">
                                        Örnek: <?php echo $current_settings['currency']; ?>100.00
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="content-col">
                            <!-- Finansal Ayarlar -->
                            <div class="content-card">
                                <h3>Finansal Ayarlar</h3>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="min_investment">Minimum Yatırım:</label>
                                        <input type="number" id="min_investment" name="min_investment" 
                                               value="<?php echo $current_settings['min_investment']; ?>" step="0.01" min="0">
                                        <small>Yapılabilecek minimum yatırım miktarı</small>
                                    </div>
                                    <div class="form-group">
                                        <label for="min_withdrawal">Minimum Çekim:</label>
                                        <input type="number" id="min_withdrawal" name="min_withdrawal" 
                                               value="<?php echo $current_settings['min_withdrawal']; ?>" step="0.01" min="0">
                                        <small>Yapılabilecek minimum çekim miktarı</small>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="withdrawal_fee">Çekim Ücreti (%):</label>
                                    <input type="number" id="withdrawal_fee" name="withdrawal_fee" 
                                           value="<?php echo $current_settings['withdrawal_fee']; ?>" step="0.1" min="0" max="50">
                                    <small>Çekim işlemlerinde alınacak komisyon oranı</small>
                                </div>
                            </div>
                            
                            <!-- Sistem Ayarları -->
                            <div class="content-card">
                                <h3>Sistem Ayarları</h3>
                                
                                <div class="form-group">
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="maintenance_mode" value="1" 
                                               <?php echo $current_settings['maintenance_mode'] == '1' ? 'checked' : ''; ?>>
                                        Bakım Modu
                                    </label>
                                    <small>Bakım modu açıkken sadece adminler siteye erişebilir.</small>
                                </div>
                                
                                <div class="form-group">
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="user_registration" value="1" 
                                               <?php echo $current_settings['user_registration'] == '1' ? 'checked' : ''; ?>>
                                        Kullanıcı Kaydı
                                    </label>
                                    <small>Yeni kullanıcı kaydına izin ver.</small>
                                </div>
                                
                                <div class="form-group">
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="email_verification" value="1" 
                                               <?php echo $current_settings['email_verification'] == '1' ? 'checked' : ''; ?>>
                                        E-posta Doğrulama
                                    </label>
                                    <small>Yeni kullanıcıların e-posta doğrulaması yapması gerekir.</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="session_timeout">Oturum Süresi (dakika):</label>
                                    <input type="number" id="session_timeout" name="session_timeout" 
                                           value="<?php echo $current_settings['session_timeout']; ?>" min="1" max="1440">
                                    <small>Kullanıcı oturumunun süresi</small>
                                </div>
                            </div>
                            
                            <!-- Güvenlik Ayarları -->
                            <div class="content-card">
                                <h3>Güvenlik Ayarları</h3>
                                
                                <div class="form-group">
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="force_ssl" value="1" 
                                               <?php echo $current_settings['force_ssl'] == '1' ? 'checked' : ''; ?>>
                                        SSL Zorunluluğu
                                    </label>
                                    <small>Tüm bağlantıların HTTPS üzerinden olmasını zorunlu kılar.</small>
                                </div>
                                
                                <div class="form-group">
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="login_attempts" value="1" 
                                               <?php echo $current_settings['login_attempts'] == '1' ? 'checked' : ''; ?>>
                                        Giriş Denemesi Sınırı
                                    </label>
                                    <small>5 başarısız girişten sonra 30 dakika bloklama.</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="password_strength">Minimum Şifre Gücü:</label>
                                    <select id="password_strength" name="password_strength">
                                        <option value="low" <?php echo $current_settings['password_strength'] == 'low' ? 'selected' : ''; ?>>Düşük (6 karakter)</option>
                                        <option value="medium" <?php echo $current_settings['password_strength'] == 'medium' ? 'selected' : ''; ?>>Orta (8 karakter, harf+sayı)</option>
                                        <option value="high" <?php echo $current_settings['password_strength'] == 'high' ? 'selected' : ''; ?>>Yüksek (10 karakter, harf+sayı+özel karakter)</option>
                                    </select>
                                    <small>Kullanıcı şifreleri için minimum güç seviyesi</small>
                                </div>
                            </div>
                            
                            <!-- Kaydet Butonu -->
                            <div class="content-card">
                                <div class="form-actions">
                                    <button type="submit" name="save_settings" class="btn btn-primary btn-large">Ayarları Kaydet</button>
                                    <button type="reset" class="btn btn-secondary">Sıfırla</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script src="../assets/js/admin.js"></script>
    <script>
    function updateCurrencyPreview() {
        const currencySelect = document.getElementById('currency');
        const currencyPreview = document.getElementById('currencyPreview');
        const selectedCurrency = currencySelect.value;
        
        currencyPreview.textContent = `Örnek: ${selectedCurrency}100.00`;
    }

    // Logo önizleme
    function previewLogo(input) {
        const preview = document.getElementById('logoPreview');
        const uploadText = document.getElementById('logoUploadText');
        
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
                uploadText.innerHTML = '<p>✅ Logo seçildi</p>';
            }
            
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    // Logo yükleme alanı işlevselliği
    const logoUploadArea = document.getElementById('logoUploadArea');
    const logoInput = document.getElementById('logo');
    
    logoUploadArea.addEventListener('click', () => {
        logoInput.click();
    });
    
    logoUploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        logoUploadArea.classList.add('dragover');
    });
    
    logoUploadArea.addEventListener('dragleave', () => {
        logoUploadArea.classList.remove('dragover');
    });
    
    logoUploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        logoUploadArea.classList.remove('dragover');
        
        if (e.dataTransfer.files.length) {
            logoInput.files = e.dataTransfer.files;
            previewLogo(logoInput);
        }
    });
    
    // Sayfa yüklendiğinde para birimi önizlemesini güncelle
    document.addEventListener('DOMContentLoaded', function() {
        updateCurrencyPreview();
    });
	
	// Banner önizleme
function previewBanner(input) {
    const preview = document.getElementById('bannerPreview');
    const uploadText = document.getElementById('bannerUploadText');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
            uploadText.innerHTML = '<p>✅ Banner seçildi</p>';
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}

// Özellik görseli önizleme
function previewFeature(input, featureKey) {
    const preview = document.getElementById('featurePreview_' + featureKey);
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}

// Banner yükleme alanı işlevselliği
const bannerUploadArea = document.getElementById('bannerUploadArea');
const bannerInput = document.getElementById('banner');

bannerUploadArea.addEventListener('click', () => {
    bannerInput.click();
});

bannerUploadArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    bannerUploadArea.classList.add('dragover');
});

bannerUploadArea.addEventListener('dragleave', () => {
    bannerUploadArea.classList.remove('dragover');
});

bannerUploadArea.addEventListener('drop', (e) => {
    e.preventDefault();
    bannerUploadArea.classList.remove('dragover');
    
    if (e.dataTransfer.files.length) {
        bannerInput.files = e.dataTransfer.files;
        previewBanner(bannerInput);
    }
});
    </script>
</body>
</html>
[file content end]