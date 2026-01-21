<?php
if (!isset($page_title)) {
    $page_title = __('site_title');
}

// Site logosunu getir
$site_logo = '';
$site_name = 'GPU Invest'; // Varsayılan site adı

try {
    // Logo kontrolü
    $logo_stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'site_logo'");
    $logo_stmt->execute();
    $logo_result = $logo_stmt->fetch();
    if ($logo_result) {
        $site_logo = $logo_result['setting_value'];
    }
    
    // Site adı kontrolü
    $name_stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'site_name'");
    $name_stmt->execute();
    $name_result = $name_stmt->fetch();
    if ($name_result) {
        $site_name = $name_result['setting_value'];
    }
} catch (Exception $e) {
    // Hata durumunda varsayılan değerler kullan
    error_log("Header logo error: " . $e->getMessage());
}

// Logo dosyasının var olup olmadığını kontrol et
$logo_exists = false;
if ($site_logo && file_exists($site_logo)) {
    $logo_exists = true;
} else {
    // assets/images/ klasöründe logo.* dosyalarını ara
    $logo_files = glob('assets/images/logo.*');
    if (!empty($logo_files)) {
        $site_logo = $logo_files[0];
        $logo_exists = true;
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['language']; ?>" data-theme="<?php echo getCurrentTheme(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <?php if (isset($admin_page) && $admin_page): ?>
    <link rel="stylesheet" href="assets/css/admin.css">
    <?php endif; ?>
    <style>
        .nav-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .site-logo {
            height: 40px;
            max-width: 150px;
            object-fit: contain;
            transition: opacity 0.3s ease;
        }
        .site-logo:hover {
            opacity: 0.9;
        }
        .nav-brand h1 {
            margin: 0;
            font-size: 1.5rem;
            color: var(--text-color);
            font-weight: 600;
        }
        .nav-brand a {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: inherit;
        }
        
        /* Mobil menü stilleri - GÜNCELLENDİ */
        .mobile-menu {
            position: fixed;
            top: 0;
            right: -100%;
            width: 300px;
            height: 100vh;
            background: var(--card-bg);
            box-shadow: -5px 0 15px rgba(0,0,0,0.1);
            transition: right 0.3s ease;
            z-index: 1000;
            overflow-y: auto;
        }
        .mobile-menu.active {
            right: 0;
        }
        .mobile-menu-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        .mobile-menu-header h3 {
            margin: 0;
            color: var(--text-color);
        }
        .mobile-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-color);
        }
        .mobile-nav {
            padding: 1rem;
        }
        .mobile-nav-item {
            display: block;
            padding: 0.75rem 1rem;
            color: var(--text-color);
            text-decoration: none;
            border-radius: 6px;
            margin-bottom: 0.5rem;
            transition: background-color 0.3s;
        }
        .mobile-nav-item:hover {
            background-color: var(--bg-color);
        }
        .mobile-language-selector {
            border-top: 1px solid var(--border-color);
            margin-top: 1rem;
            padding-top: 1rem;
        }
        .mobile-language-options {
            display: flex;
            flex-direction: column;
            margin-top: 0.5rem;
        }
        .mobile-language-option {
            padding: 0.75rem 1rem;
            text-decoration: none;
            color: var(--text-color);
            border-radius: 6px;
            margin: 0.25rem 0;
            transition: background-color 0.3s;
        }
        .mobile-language-option:hover,
        .mobile-language-option.active {
            background-color: var(--primary-color);
            color: white;
        }
        .admin-link {
            background: linear-gradient(45deg, #e74c3c, #c0392b);
            color: white !important;
        }
        .logout-link {
            background: var(--error-color);
            color: white !important;
        }
        
        /* Mobil için logo ayarları */
        @media (max-width: 768px) {
            .site-logo {
                height: 35px;
                max-width: 120px;
            }
            .nav-brand h1 {
                font-size: 1.2rem;
            }
            /* Logo varsa site adını gizle */
            .nav-brand.has-logo h1 {
                display: none;
            }
            
            /* Masaüstü menüyü mobilde gizle */
            .nav-menu {
                display: none;
            }
        }
        
        /* Masaüstü menüyü göster */
        @media (min-width: 769px) {
            .nav-toggle {
                display: none;
            }
            .mobile-menu {
                display: none;
            }
        }
        
        /* Çok küçük ekranlar için */
        @media (max-width: 480px) {
            .site-logo {
                height: 30px;
                max-width: 100px;
            }
            .mobile-menu {
                width: 280px;
            }
        }
        
        /* Hamburger menü butonu */
        .nav-toggle {
            display: none;
            flex-direction: column;
            justify-content: space-between;
            width: 30px;
            height: 21px;
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
        }
        
        .nav-toggle span {
            display: block;
            height: 3px;
            width: 100%;
            background-color: var(--text-color);
            border-radius: 3px;
            transition: all 0.3s ease;
        }
        
        @media (max-width: 768px) {
            .nav-toggle {
                display: flex;
            }
        }
    </style>
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="nav-brand <?php echo $logo_exists ? 'has-logo' : ''; ?>">
                <a href="index.php">
                    <?php if ($logo_exists): ?>
                        <img src="<?php echo $site_logo; ?>" alt="<?php echo htmlspecialchars($site_name); ?>" class="site-logo"
                             onerror="this.style.display='none'; document.querySelector('.nav-brand').classList.remove('has-logo');">
                    <?php endif; ?>
                    <h1><?php echo htmlspecialchars($site_name); ?></h1>
                </a>
            </div>
            
            <!-- Masaüstü Menü -->
            <div class="nav-menu" id="navMenu">
                <?php if (isLoggedIn()): ?>
                    <a href="dashboard.php" class="nav-link"><?php echo __('dashboard'); ?></a>
                    <a href="my_gpus.php" class="nav-link"><?php echo __('my_gpus'); ?></a>
                    <a href="transactions.php" class="nav-link"><?php echo __('transactions'); ?></a>
                    <a href="invest.php" class="nav-link"><?php echo __('invest'); ?></a>
                    <a href="withdraw.php" class="nav-link"><?php echo __('withdraw'); ?></a>
                    <a href="buy_gpu.php" class="nav-link"><?php echo __('buy_gpu'); ?></a>
                    
                    <?php if (isAdmin()): ?>
                        <a href="admin/" class="nav-link"><?php echo __('admin_panel'); ?></a>
                    <?php endif; ?>
                    
                    <div class="dropdown">
                        <button class="nav-link dropdown-toggle">
                            <?php echo $_SESSION['username']; ?>
                        </button>
                        <div class="dropdown-content">
                            <a href="settings.php"><?php echo __('settings'); ?></a>
                            <a href="includes/logout.php"><?php echo __('logout'); ?></a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="index.php" class="nav-link"><?php echo __('home'); ?></a>
                    <a href="login.php" class="nav-link"><?php echo __('login'); ?></a>
                    <a href="register.php" class="nav-link"><?php echo __('register'); ?></a>
                <?php endif; ?>
                
                <!-- Dil seçici -->
                <div class="dropdown">
                    <button class="nav-link dropdown-toggle">
                        <?php echo strtoupper($_SESSION['language']); ?>
                    </button>
                    <div class="dropdown-content">
                        <a href="?lang=tr">TR - Türkçe</a>
                        <a href="?lang=en">EN - English</a>
                        <a href="?lang=ru">RU - Русский</a>
                        <a href="?lang=uz">UZ - O'zbekcha</a>
                    </div>
                </div>
            </div>
            
            <!-- Hamburger Menü Butonu -->
            <button class="nav-toggle" id="navToggle">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </nav>
        
        <!-- Mobil Menü -->
        <div class="mobile-menu" id="mobileMenu">
            <div class="mobile-menu-header">
                <h3>Menu</h3>
                <button class="mobile-close" id="mobileClose">×</button>
            </div>
            <nav class="mobile-nav">
                <?php if (isLoggedIn()): ?>
                    <a href="dashboard.php" class="mobile-nav-item"><?php echo __('dashboard'); ?></a>
                    <a href="my_gpus.php" class="mobile-nav-item"><?php echo __('my_gpus'); ?></a>
                    <a href="transactions.php" class="mobile-nav-item"><?php echo __('transactions'); ?></a>
                    <a href="invest.php" class="mobile-nav-item"><?php echo __('invest'); ?></a>
                    <a href="withdraw.php" class="mobile-nav-item"><?php echo __('withdraw'); ?></a>
                    <a href="buy_gpu.php" class="mobile-nav-item"><?php echo __('buy_gpu'); ?></a>
                    
                    <?php if (isAdmin()): ?>
                        <a href="admin/" class="mobile-nav-item admin-link">⚡ <?php echo __('admin_panel'); ?></a>
                    <?php endif; ?>
                    
                    <a href="settings.php" class="mobile-nav-item">⚙️ <?php echo __('settings'); ?></a>
                    <a href="includes/logout.php" class="mobile-nav-item logout-link">🚪 <?php echo __('logout'); ?></a>
                <?php else: ?>
                    <a href="index.php" class="mobile-nav-item">🏠 <?php echo __('home'); ?></a>
                    <a href="login.php" class="mobile-nav-item">🔑 <?php echo __('login'); ?></a>
                    <a href="register.php" class="mobile-nav-item">📝 <?php echo __('register'); ?></a>
                <?php endif; ?>
                
                <!-- Mobil dil seçenekleri -->
                <div class="mobile-language-selector">
                    <span class="mobile-nav-item">🌐 <?php echo __('language'); ?></span>
                    <div class="mobile-language-options">
                        <a href="?lang=tr" class="mobile-language-option <?php echo $_SESSION['language'] == 'tr' ? 'active' : ''; ?>">
                            🇹🇷 Türkçe
                        </a>
                        <a href="?lang=en" class="mobile-language-option <?php echo $_SESSION['language'] == 'en' ? 'active' : ''; ?>">
                            🇺🇸 English
                        </a>
                        <a href="?lang=ru" class="mobile-language-option <?php echo $_SESSION['language'] == 'ru' ? 'active' : ''; ?>">
                            🇷🇺 Русский
                        </a>
                        <a href="?lang=uz" class="mobile-language-option <?php echo $_SESSION['language'] == 'uz' ? 'active' : ''; ?>">
                            🇺🇿 O'zbekcha
                        </a>
                    </div>
                </div>
            </nav>
        </div>
    </header>

    <main class="container">

    <script>
    // Mobil menü fonksiyonları
    function openMobileMenu() {
        document.getElementById('mobileMenu').classList.add('active');
        document.body.style.overflow = 'hidden'; // Sayfa kaydırmayı engelle
    }
    
    function closeMobileMenu() {
        document.getElementById('mobileMenu').classList.remove('active');
        document.body.style.overflow = ''; // Sayfa kaydırmayı geri aç
    }
    
    // Hamburger menü toggle
    document.getElementById('navToggle').addEventListener('click', function(e) {
        e.stopPropagation();
        openMobileMenu();
    });
    
    // Mobil menü kapatma butonu
    document.getElementById('mobileClose').addEventListener('click', function(e) {
        e.stopPropagation();
        closeMobileMenu();
    });
    
    // Mobil menü dışına tıklayınca kapat
    document.addEventListener('click', function(event) {
        const mobileMenu = document.getElementById('mobileMenu');
        const navToggle = document.getElementById('navToggle');
        
        if (mobileMenu.classList.contains('active') && 
            !mobileMenu.contains(event.target) && 
            !navToggle.contains(event.target)) {
            closeMobileMenu();
        }
    });
    
    // ESC tuşuna basınca mobil menüyü kapat
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeMobileMenu();
        }
    });
    
    // Dil değiştirme linklerine tıklayınca mobil menüyü kapat
    document.querySelectorAll('.mobile-language-option').forEach(link => {
        link.addEventListener('click', function() {
            setTimeout(() => {
                closeMobileMenu();
            }, 100);
        });
    });
    
    // Mobil menü linklerine tıklayınca menüyü kapat
    document.querySelectorAll('.mobile-nav-item').forEach(link => {
        link.addEventListener('click', function() {
            setTimeout(() => {
                closeMobileMenu();
            }, 100);
        });
    });
    
    // Sayfa yüklendiğinde mobil menüyü kontrol et
    document.addEventListener('DOMContentLoaded', function() {
        // Masaüstü menüyü mobilde gizle, mobilde göster
        if (window.innerWidth <= 768) {
            document.getElementById('navMenu').style.display = 'none';
        }
    });
    
    // Pencere boyutu değiştiğinde kontrol et
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            document.getElementById('navMenu').style.display = 'flex';
            closeMobileMenu(); // Masaüstü görünümde mobil menüyü kapat
        } else {
            document.getElementById('navMenu').style.display = 'none';
        }
    });
    </script>