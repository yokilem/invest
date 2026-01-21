<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$page_title = __('site_title');

// Banner ve özellik görsellerini getir
$home_banner = '';
$feature_images = [];

try {
    // Banner kontrolü
    $banner_stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'home_banner'");
    $banner_stmt->execute();
    $banner_result = $banner_stmt->fetch();
    if ($banner_result) {
        $home_banner = $banner_result['setting_value'];
    }
    
    // Özellik görsellerini getir
    $features = ['passive_income', 'high_commission', 'secure_investment'];
    foreach ($features as $feature) {
        $stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'feature_$feature'");
        $stmt->execute();
        $result = $stmt->fetch();
        if ($result) {
            $feature_images[$feature] = $result['setting_value'];
        }
    }
} catch (Exception $e) {
    error_log("Index page images error: " . $e->getMessage());
}

// Banner dosyasının var olup olmadığını kontrol et
$banner_exists = false;
if ($home_banner && file_exists($home_banner)) {
    $banner_exists = true;
}
?>
<?php include 'includes/header.php'; ?>

<!-- Hero Section with Banner -->
<section class="hero" <?php if ($banner_exists): ?>style="background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('<?php echo $home_banner; ?>') center/cover;"<?php endif; ?>>
    <div class="hero-content">
        <h2><?php echo __('welcome_to_gpu_investment'); ?></h2>
        <p><?php echo __('hero_description'); ?></p>
        <div class="hero-buttons">
            <?php if (!isLoggedIn()): ?>
                <a href="register.php" class="btn btn-primary"><?php echo __('get_started'); ?></a>
                <a href="login.php" class="btn btn-secondary"><?php echo __('login'); ?></a>
            <?php else: ?>
                <a href="dashboard.php" class="btn btn-primary"><?php echo __('dashboard'); ?></a>
                <a href="buy_gpu.php" class="btn btn-secondary"><?php echo __('buy_gpu'); ?></a>
                <?php if (isAdmin()): ?>
                    <a href="admin/" class="btn btn-warning">Admin Panel</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Features Section with Images -->
<section class="features">
    <div class="feature-grid">
        <div class="feature-card">
            <div class="feature-icon">
                <?php if (isset($feature_images['passive_income']) && file_exists($feature_images['passive_income'])): ?>
                    <img src="<?php echo $feature_images['passive_income']; ?>" alt="<?php echo __('passive_income'); ?>" 
                         class="feature-image">
                    <div class="feature-emoji" style="display: none;">💰</div>
                <?php else: ?>
                    <div class="feature-emoji">💰</div>
                <?php endif; ?>
            </div>
            <div class="feature-content">
                <h3><?php echo __('passive_income'); ?></h3>
                <p><?php echo __('passive_income_desc'); ?></p>
            </div>
        </div>
        
        <div class="feature-card">
            <div class="feature-icon">
                <?php if (isset($feature_images['high_commission']) && file_exists($feature_images['high_commission'])): ?>
                    <img src="<?php echo $feature_images['high_commission']; ?>" alt="<?php echo __('high_commission'); ?>" 
                         class="feature-image">
                    <div class="feature-emoji" style="display: none;">📈</div>
                <?php else: ?>
                    <div class="feature-emoji">📈</div>
                <?php endif; ?>
            </div>
            <div class="feature-content">
                <h3><?php echo __('high_commission'); ?></h3>
                <p><?php echo __('high_commission_desc'); ?></p>
            </div>
        </div>
        
        <div class="feature-card">
            <div class="feature-icon">
                <?php if (isset($feature_images['secure_investment']) && file_exists($feature_images['secure_investment'])): ?>
                    <img src="<?php echo $feature_images['secure_investment']; ?>" alt="<?php echo __('secure_investment'); ?>" 
                         class="feature-image">
                    <div class="feature-emoji" style="display: none;">🛡️</div>
                <?php else: ?>
                    <div class="feature-emoji">🛡️</div>
                <?php endif; ?>
            </div>
            <div class="feature-content">
                <h3><?php echo __('secure_investment'); ?></h3>
                <p><?php echo __('secure_investment_desc'); ?></p>
            </div>
        </div>
    </div>
</section>

<!-- GPU List Section (Mevcut kısım aynı kalacak) -->
<section class="gpu-list">
    <h2><?php echo __('available_gpus'); ?></h2>
    <div class="gpu-grid">
        <?php
        $gpus = getGPUs();
        foreach ($gpus as $gpu): 
            $dailyEarning = calculateDailyEarning($gpu['monthly_income']);
        ?>
            <div class="gpu-card">
                <img src="<?php echo $gpu['image_path'] ?: 'assets/images/default-gpu.jpg'; ?>" 
                     alt="<?php echo $gpu['name']; ?>" 
                     class="gpu-image">
                <div class="gpu-info">
                    <h3><?php echo $gpu['name']; ?></h3>
                    <div class="gpu-details">
                        <div class="detail-item">
                            <span class="label"><?php echo __('gpu_price'); ?>:</span>
                            <span class="value"><?php echo formatPrice($gpu['price']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="label"><?php echo __('monthly_income'); ?>:</span>
                            <span class="value"><?php echo formatPrice($gpu['monthly_income']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="label"><?php echo __('daily_income'); ?>:</span>
                            <span class="value"><?php echo formatPrice($dailyEarning); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="label"><?php echo __('commission_rate'); ?>:</span>
                            <span class="value">%<?php echo $gpu['commission_rate']; ?></span>
                        </div>
                    </div>
                </div>
                <?php if (isLoggedIn()): ?>
                    <a href="gpu_detail.php?id=<?php echo $gpu['id']; ?>" class="btn btn-primary">
                        <?php echo __('view_details'); ?>
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-primary">
                        <?php echo __('buy_gpu'); ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<style>
/* Hero Section with Banner */
.hero {
    padding: 100px 0;
    text-align: center;
    color: white;
    position: relative;
    <?php if (!$banner_exists): ?>
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    <?php endif; ?>
}

.hero-content {
    position: relative;
    z-index: 2;
    max-width: 800px;
    margin: 0 auto;
    padding: 0 20px;
}

.hero-content h2 {
    font-size: 3rem;
    margin-bottom: 1rem;
    font-weight: 700;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
}

.hero-content p {
    font-size: 1.2rem;
    margin-bottom: 2rem;
    opacity: 0.9;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
}

.hero-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

/* Features Section */
.features {
    padding: 80px 0;
    background: var(--bg-color);
}

.feature-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.feature-card {
    background: var(--card-bg);
    padding: 0; /* Padding'i kaldırdık, içerik kendi padding'ine sahip olacak */
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: 1px solid var(--border-color);
    overflow: hidden; /* Görsellerin köşeleri yuvarlak olsun */
    display: flex;
    flex-direction: column;
    height: 100%; /* Tüm kartlar aynı yükseklikte */
}

.feature-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.feature-icon {
    margin-bottom: 0; /* Margin'i kaldırdık */
    height: 200px; /* Sabit yükseklik */
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); /* Görsel yoksa gradient */
    position: relative;
    overflow: hidden;
}

.feature-image {
    width: 100%; /* Kutu genişliğini tam doldur */
    height: 100%; /* Kutu yüksekliğini tam doldur */
    object-fit: cover; /* Görseli kutuya sığdır, keserek tam doldur */
    object-position: center; /* Görseli ortala */
    display: block;
}

.feature-emoji {
    font-size: 4rem;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 1;
    background: rgba(255,255,255,0.9);
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.feature-content {
    padding: 2rem; /* İçerik için padding */
    flex: 1; /* İçeriğin kalan alanı doldurmasını sağla */
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.feature-card h3 {
    font-size: 1.5rem;
    margin-bottom: 1rem;
    color: var(--text-color);
    font-weight: 600;
}

.feature-card p {
    color: var(--text-color);
    opacity: 0.8;
    line-height: 1.6;
    margin: 0;
}

/* GPU List Section */
.gpu-list {
    padding: 80px 0;
}

.gpu-list h2 {
    text-align: center;
    font-size: 2.5rem;
    margin-bottom: 3rem;
    color: var(--text-color);
}

.gpu-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.gpu-card {
    background: var(--card-bg);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
    border: 1px solid var(--border-color);
}

.gpu-card:hover {
    transform: translateY(-5px);
}

.gpu-image {
    width: 100%;
    height: 200px;
    object-fit: cover;
}

.gpu-info {
    padding: 1.5rem;
}

.gpu-info h3 {
    margin: 0 0 1rem 0;
    color: var(--text-color);
    font-size: 1.3rem;
}

.gpu-details {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--border-color);
}

.detail-item:last-child {
    border-bottom: none;
}

.detail-item .label {
    color: var(--text-color);
    opacity: 0.8;
}

.detail-item .value {
    font-weight: 600;
    color: var(--text-color);
}

.gpu-card .btn {
    margin: 0 1.5rem 1.5rem;
    width: calc(100% - 3rem);
}

/* Responsive Tasarım */
@media (max-width: 768px) {
    .hero-content h2 {
        font-size: 2rem;
    }
    
    .hero-content p {
        font-size: 1rem;
    }
    
    .hero-buttons {
        flex-direction: column;
        align-items: center;
    }
    
    .hero-buttons .btn {
        width: 200px;
    }
    
    .feature-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .feature-card {
        padding: 1.5rem;
    }
    
    .gpu-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .hero {
        padding: 60px 0;
    }
    
    .hero-content h2 {
        font-size: 1.8rem;
    }
    
    .features {
        padding: 60px 0;
    }
    
    .gpu-list {
        padding: 60px 0;
    }
    
    .gpu-list h2 {
        font-size: 2rem;
    }
}
</style>

<?php include 'includes/footer.php'; ?>