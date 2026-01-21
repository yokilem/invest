<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$page_title = __("my_gpus");

// Kullanıcının GPU'larını getir
$user_gpus = getUserGPUs($user_id);

// Toplam gelirleri hesapla
$total_daily_income = 0;
$total_monthly_income = 0;
$total_yearly_income = 0;
$total_investment = 0;

foreach ($user_gpus as $gpu) {
    $daily_income = calculateDailyEarning($gpu['monthly_income']);
    $total_daily_income += $daily_income;
    $total_monthly_income += $gpu['monthly_income'];
    $total_yearly_income += calculateYearlyIncome($gpu['monthly_income']);
    $total_investment += $gpu['price']; // GPU fiyatını topla
}

// Ortalama getiri oranını hesapla
$average_return_rate = 0;
if ($total_investment > 0) {
    $average_return_rate = ($total_monthly_income / $total_investment) * 100;
}

// Grafik verileri için hesaplamalar
$minute_income = $total_daily_income / 24 / 60;
$hourly_income = $total_daily_income / 24;
$weekly_income = $total_daily_income * 7;

// Son 7 günlük veri (simülasyon)
$weekly_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('d M', strtotime("-$i days"));
    $earning = $total_daily_income * (0.9 + (mt_rand(0, 20) / 100));
    $weekly_data[] = ['date' => $date, 'earning' => $earning];
}

// GPU dağılım verileri
$gpu_distribution = [];
foreach ($user_gpus as $gpu) {
    $daily = calculateDailyEarning($gpu['monthly_income']);
    $percentage = $total_daily_income > 0 ? ($daily / $total_daily_income) * 100 : 0;
    $gpu_distribution[] = [
        'name' => $gpu['name'],
        'daily' => $daily,
        'percentage' => $percentage
    ];
}
?>
<?php include 'includes/header.php'; ?>

<div class="page-header">
    <h1><?php echo __("my_gpus"); ?></h1>
    <p><?php echo __('my_gpus_description'); ?></p>
</div>

<!-- İstatistik Kartları -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon">🖥️</div>
        <div class="stat-info">
            <h3><?php echo __('total_gpus'); ?></h3>
            <p class="stat-value"><?php echo count($user_gpus); ?></p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">📈</div>
        <div class="stat-info">
            <h3><?php echo __('daily_income'); ?></h3>
            <p class="stat-value"><?php echo formatPrice($total_daily_income); ?></p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">💰</div>
        <div class="stat-info">
            <h3><?php echo __('monthly_income'); ?></h3>
            <p class="stat-value"><?php echo formatPrice($total_monthly_income); ?></p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">🎯</div>
        <div class="stat-info">
            <h3><?php echo __('return_rate'); ?></h3>
            <p class="stat-value">%<?php echo number_format($average_return_rate, 2); ?></p>
        </div>
    </div>
</div>

<div class="content-row">
    <div class="content-col">
        <!-- Mini Grafik: Zaman Bazlı Kazanç -->
        <div class="content-card mini-chart">
            <h3>⏰ <?php echo __('time_earnings'); ?></h3>
            <div class="mini-chart-grid">
                <div class="mini-chart-item">
                    <div class="mini-chart-icon">🕒</div>
                    <div class="mini-chart-info">
                        <div class="mini-chart-label"><?php echo __('per_minute'); ?></div>
                        <div class="mini-chart-value"><?php echo formatPrice($minute_income); ?></div>
                    </div>
                </div>
                <div class="mini-chart-item">
                    <div class="mini-chart-icon">🕐</div>
                    <div class="mini-chart-info">
                        <div class="mini-chart-label"><?php echo __('per_hour'); ?></div>
                        <div class="mini-chart-value"><?php echo formatPrice($hourly_income); ?></div>
                    </div>
                </div>
                <div class="mini-chart-item">
                    <div class="mini-chart-icon">📅</div>
                    <div class="mini-chart-info">
                        <div class="mini-chart-label"><?php echo __('per_week'); ?></div>
                        <div class="mini-chart-value"><?php echo formatPrice($weekly_income); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="content-card">
            <h3><?php echo __('my_gpu_list'); ?></h3>
            
            <?php if (empty($user_gpus)): ?>
                <div class="no-data">
                    <p><?php echo __('no_gpus_owned'); ?></p>
                    <a href="buy_gpu.php" class="btn btn-primary"><?php echo __('buy_first_gpu'); ?></a>
                </div>
            <?php else: ?>
                <div class="gpu-list">
                    <?php foreach ($user_gpus as $gpu): 
                        $daily_income = calculateDailyEarning($gpu['monthly_income']);
                        $yearly_income = calculateYearlyIncome($gpu['monthly_income']);
                        $return_rate = calculateReturnRate($gpu['price'], $gpu['monthly_income']);
                    ?>
                        <div class="gpu-item">
                            <div class="gpu-image">
                                <img src="<?php echo $gpu['image_path'] ?: 'https://via.placeholder.com/100x80/3498db/ffffff?text=GPU'; ?>" 
                                     alt="<?php echo $gpu['name']; ?>">
                            </div>
                            <div class="gpu-details">
                                <h4><?php echo $gpu['name']; ?></h4>
                                <div class="gpu-stats">
                                    <div class="stat">
                                        <span class="label"><?php echo __('daily_income'); ?>:</span>
                                        <span class="value"><?php echo formatPrice($daily_income); ?></span>
                                    </div>
                                    <div class="stat">
                                        <span class="label"><?php echo __('monthly_income'); ?>:</span>
                                        <span class="value"><?php echo formatPrice($gpu['monthly_income']); ?></span>
                                    </div>
                                    <div class="stat">
                                        <span class="label"><?php echo __('yearly_income'); ?>:</span>
                                        <span class="value"><?php echo formatPrice($yearly_income); ?></span>
                                    </div>
                                    <div class="stat">
                                        <span class="label"><?php echo __('return_rate'); ?>:</span>
                                        <span class="value">%<?php echo number_format($return_rate, 2); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="gpu-purchase-date">
                                <small><?php echo __('purchased_on'); ?>: <?php echo date('d.m.Y', strtotime($gpu['purchase_date'])); ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="content-col">
        <!-- Mini Grafik: GPU Dağılımı -->
<div class="content-card mini-chart">
    <h3>📊 <?php echo __('gpu_distribution'); ?></h3>
    <div class="distribution-container">
        <?php foreach ($gpu_distribution as $index => $gpu): ?>
            <div class="distribution-item" data-percentage="<?php echo $gpu['percentage']; ?>">
                <div class="distribution-header">
                    <div class="gpu-info">
                        <span class="gpu-name"><?php echo $gpu['name']; ?></span>
                        <span class="gpu-amount"><?php echo formatPrice($gpu['daily']); ?>/gün</span>
                    </div>
                    <span class="gpu-percentage">%<?php echo number_format($gpu['percentage'], 1); ?></span>
                </div>
                <div class="distribution-visual">
                    <div class="distribution-bar">
                        <div class="distribution-fill" style="width: 0%" data-target="<?php echo $gpu['percentage']; ?>"></div>
                    </div>
                    <div class="distribution-animation">
                        <div class="animation-particle"></div>
                        <div class="animation-particle"></div>
                        <div class="animation-particle"></div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

        <div class="content-card">
            <h3><?php echo __('income_summary'); ?></h3>
            <div class="summary-stats">
                <div class="summary-item">
                    <span class="summary-label"><?php echo __('total_investment'); ?>:</span>
                    <span class="summary-value"><?php echo formatPrice($total_investment); ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label"><?php echo __('daily_income'); ?>:</span>
                    <span class="summary-value"><?php echo formatPrice($total_daily_income); ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label"><?php echo __('monthly_income'); ?>:</span>
                    <span class="summary-value"><?php echo formatPrice($total_monthly_income); ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label"><?php echo __('yearly_income'); ?>:</span>
                    <span class="summary-value"><?php echo formatPrice($total_yearly_income); ?></span>
                </div>
                <div class="summary-item total">
                    <span class="summary-label"><?php echo __('average_return_rate'); ?>:</span>
                    <span class="summary-value">%<?php echo number_format($average_return_rate, 2); ?></span>
                </div>
            </div>
        </div>
        
        <div class="content-card">
            <h3><?php echo __('quick_actions'); ?></h3>
            <div class="quick-actions-grid">
                <a href="buy_gpu.php" class="quick-action">
                    <div class="action-icon">🖥️</div>
                    <span><?php echo __('buy_gpu'); ?></span>
                </a>
                <a href="invest.php" class="quick-action">
                    <div class="action-icon">💰</div>
                    <span><?php echo __('invest'); ?></span>
                </a>
                <a href="withdraw.php" class="quick-action">
                    <div class="action-icon">📤</div>
                    <span><?php echo __('withdraw'); ?></span>
                </a>
                <a href="transactions.php" class="quick-action">
                    <div class="action-icon">📊</div>
                    <span><?php echo __('transactions'); ?></span>
                </a>
            </div>
        </div>
    </div>
</div>

<style>
/* Mini Grafik Stilleri */
.mini-chart {
    margin-bottom: 1.5rem;
}

.mini-chart-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-top: 1rem;
}

.mini-chart-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    background: var(--bg-color);
    border-radius: 8px;
    border-left: 4px solid #3498db;
}

.mini-chart-icon {
    font-size: 1.5rem;
    flex-shrink: 0;
}

.mini-chart-info {
    flex: 1;
}

.mini-chart-label {
    display: block;
    font-size: 0.8rem;
    color: var(--text-color);
    opacity: 0.8;
    margin-bottom: 0.25rem;
}

.mini-chart-value {
    display: block;
    font-size: 1rem;
    font-weight: bold;
    color: var(--success-color);
}

/* Dağılım Çubukları */
/* GPU Dağılımı Animasyon Stilleri */
.distribution-container {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-top: 1rem;
}

.distribution-item {
    padding: 1rem;
    background: var(--bg-color);
    border-radius: 10px;
    border: 1px solid var(--border-color);
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
}

.distribution-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.distribution-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
}

.gpu-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.gpu-name {
    font-weight: 600;
    color: var(--text-color);
    font-size: 0.95rem;
}

.gpu-amount {
    font-size: 0.8rem;
    color: var(--success-color);
    font-weight: 500;
}

.gpu-percentage {
    font-weight: bold;
    color: var(--primary-color);
    font-size: 1.1rem;
    background: rgba(52, 152, 219, 0.1);
    padding: 0.25rem 0.5rem;
    border-radius: 20px;
}

.distribution-visual {
    position: relative;
}

.distribution-bar {
    height: 8px;
    background: var(--border-color);
    border-radius: 4px;
    overflow: hidden;
    position: relative;
}

.distribution-fill {
    height: 100%;
    background: linear-gradient(90deg, #3498db, #2ecc71);
    border-radius: 4px;
    width: 0%;
    transition: width 1.5s ease-in-out;
    position: relative;
    overflow: hidden;
}

.distribution-fill::after {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
    animation: shine 2s infinite;
}

.distribution-animation {
    position: absolute;
    top: 50%;
    right: 0;
    transform: translateY(-50%);
    display: flex;
    gap: 2px;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.distribution-item:hover .distribution-animation {
    opacity: 1;
}

.animation-particle {
    width: 3px;
    height: 3px;
    background: var(--primary-color);
    border-radius: 50%;
    animation: particleFloat 1.5s infinite ease-in-out;
}

.animation-particle:nth-child(1) { animation-delay: 0s; }
.animation-particle:nth-child(2) { animation-delay: 0.3s; }
.animation-particle:nth-child(3) { animation-delay: 0.6s; }

/* Animasyonlar */
@keyframes shine {
    0% { left: -100%; }
    100% { left: 100%; }
}

@keyframes particleFloat {
    0%, 100% { 
        transform: translateY(0px);
        opacity: 1;
    }
    50% { 
        transform: translateY(-5px);
        opacity: 0.7;
    }
}

@keyframes fillAnimation {
    0% { 
        width: 0%;
        opacity: 0.7;
    }
    100% { 
        width: var(--target-width);
        opacity: 1;
    }
}

/* Progress bar renkleri için sınıflar */
.distribution-item:nth-child(1) .distribution-fill {
    background: linear-gradient(90deg, #3498db, #2980b9);
}

.distribution-item:nth-child(2) .distribution-fill {
    background: linear-gradient(90deg, #2ecc71, #27ae60);
}

.distribution-item:nth-child(3) .distribution-fill {
    background: linear-gradient(90deg, #9b59b6, #8e44ad);
}

.distribution-item:nth-child(4) .distribution-fill {
    background: linear-gradient(90deg, #e74c3c, #c0392b);
}

.distribution-item:nth-child(5) .distribution-fill {
    background: linear-gradient(90deg, #f39c12, #d35400);
}

.distribution-item:nth-child(6) .distribution-fill {
    background: linear-gradient(90deg, #1abc9c, #16a085);
}

/* Responsive */
@media (max-width: 768px) {
    .distribution-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .gpu-percentage {
        align-self: flex-end;
    }
    
    .distribution-item {
        padding: 0.75rem;
    }
}

/* Mevcut Stiller */
.gpu-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.gpu-item {
    display: flex;
    align-items: center;
    padding: 1rem;
    background: var(--bg-color);
    border-radius: 8px;
    border: 1px solid var(--border-color);
    gap: 1rem;
}

.gpu-image img {
    width: 80px;
    height: 60px;
    object-fit: cover;
    border-radius: 6px;
}

.gpu-details {
    flex: 1;
}

.gpu-details h4 {
    margin: 0 0 0.5rem 0;
    color: var(--text-color);
}

.gpu-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.5rem;
}

.stat {
    display: flex;
    justify-content: space-between;
    font-size: 0.9rem;
}

.stat .label {
    color: var(--text-color);
    opacity: 0.8;
}

.stat .value {
    font-weight: 600;
    color: var(--success-color);
}

.gpu-purchase-date {
    text-align: right;
}

.summary-stats {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    background: var(--bg-color);
    border-radius: 6px;
}

.summary-item.total {
    background: var(--primary-color);
    color: white;
    font-weight: bold;
}

.quick-actions-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.quick-action {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 1rem;
    background: var(--bg-color);
    border-radius: 8px;
    text-decoration: none;
    color: var(--text-color);
    transition: transform 0.3s ease;
}

.quick-action:hover {
    transform: translateY(-2px);
}

.action-icon {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

/* Responsive */
@media (max-width: 768px) {
    .mini-chart-grid {
        grid-template-columns: 1fr;
    }
    
    .gpu-stats {
        grid-template-columns: 1fr;
    }
    
    .quick-actions-grid {
        grid-template-columns: 1fr;
    }
    
    .gpu-item {
        flex-direction: column;
        text-align: center;
    }
    
    .gpu-purchase-date {
        text-align: center;
    }
}
</style>


<script>
// GPU Dağılım Animasyonu
document.addEventListener('DOMContentLoaded', function() {
    const distributionItems = document.querySelectorAll('.distribution-item');
    
    distributionItems.forEach((item, index) => {
        // Gecikmeli animasyon
        setTimeout(() => {
            const fill = item.querySelector('.distribution-fill');
            const targetWidth = fill.getAttribute('data-target');
            
            // Animasyonu başlat
            fill.style.width = targetWidth + '%';
            
            // Hover efekti için event listener
            item.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
            });
            
            item.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = 'none';
            });
            
        }, index * 300); // Her item için 300ms gecikme
    });
    
    // Sayfa scroll olduğunda animasyonu tetikle
    const observerOptions = {
        threshold: 0.3,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const fill = entry.target.querySelector('.distribution-fill');
                const targetWidth = fill.getAttribute('data-target');
                fill.style.width = targetWidth + '%';
            }
        });
    }, observerOptions);
    
    distributionItems.forEach(item => {
        observer.observe(item);
    });
});
</script>

<?php include 'includes/footer.php'; ?>