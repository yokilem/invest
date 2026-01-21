<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$page_title = __('dashboard');

// Kullanıcı bilgilerini getir
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// 24 saat dolmuş GPU'lar için kazanç hesapla
$today_earnings = calculate24HourEarnings($user_id);

// GPU aktivasyon durumlarını getir
$user_gpus = checkGPUActivationStatus($user_id);

// İstatistikleri hesapla - GÜNCELLENDİ
$total_monthly_income = 0;
$total_daily_income = 0;
$active_gpus = 0;
$counting_down_gpus = 0;
$waiting_gpus = 0;

foreach ($user_gpus as $gpu) {
    if ($gpu['status'] === 'earning') {
        // SADECE AKTİF (kazanç sağlayan) GPU'ları say
        $total_monthly_income += $gpu['monthly_income'];
        $total_daily_income += calculateDailyEarning($gpu['monthly_income']);
        $active_gpus++;
    } elseif ($gpu['status'] === 'counting_down') {
        $counting_down_gpus++;
    } elseif ($gpu['status'] === 'waiting_activation') {
        $waiting_gpus++;
    }
}

// Bakiyeler
$investment_balance = getUserInvestmentBalance($user_id);
$earnings_balance = getUserEarningsBalance($user_id);

?>
<?php include 'includes/header.php'; ?>

<div class="dashboard-header">
    <h1><?php echo __('welcome'); ?>, <?php echo $user['username']; ?>!</h1>
    <p><?php echo __('dashboard_description'); ?></p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon">💰</div>
        <div class="stat-info">
            <h3><?php echo __('investment_balance'); ?></h3>
            <p class="stat-value"><?php echo formatPrice($investment_balance); ?></p>
            <small><?php echo __('for_buying_gpu'); ?></small>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">💸</div>
        <div class="stat-info">
            <h3><?php echo __('earnings_balance'); ?></h3>
            <p class="stat-value"><?php echo formatPrice($earnings_balance); ?></p>
            <small><?php echo __('for_withdrawal'); ?></small>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">📈</div>
        <div class="stat-info">
            <h3><?php echo __('daily_income'); ?></h3>
            <p class="stat-value"><?php echo formatPrice($total_daily_income); ?></p>
            <small><?php echo __('from_active_gpus'); ?></small>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">🖥️</div>
        <div class="stat-info">
            <h3><?php echo __('active_gpus'); ?></h3>
            <p class="stat-value"><?php echo $active_gpus; ?></p>
            <small><?php echo __('earning_gpus'); ?></small>
        </div>
    </div>
</div>

<!-- Geri kalan kod aynı kalacak -->
<div class="dashboard-content">
    <div class="content-row">
        <div class="content-col">
            <div class="content-card">
                <h3><?php echo __('my_gpus'); ?></h3>
                <?php if (empty($user_gpus)): ?>
                    <p class="no-data"><?php echo __('no_gpus_owned'); ?></p>
                    <a href="buy_gpu.php" class="btn btn-primary"><?php echo __('buy_first_gpu'); ?></a>
                <?php else: ?>
                    <div class="gpu-status-list">
                        <?php foreach ($user_gpus as $gpu): ?>
                            <div class="gpu-status-item">
                                <div class="gpu-info">
                                    <h4><?php echo $gpu['name']; ?></h4>
                                    <div class="gpu-details">
                                        <span class="gpu-income">
                                            <?php echo formatPrice(calculateDailyEarning($gpu['monthly_income'])); ?> / <?php echo __('daily'); ?>
                                        </span>
                                        <span class="gpu-status status-<?php echo $gpu['status']; ?>">
                                            <?php 
                                            if ($gpu['status'] === 'waiting_activation') {
                                                echo __('waiting_activation');
                                            } elseif ($gpu['status'] === 'counting_down') {
                                                $hours_left = 24 - $gpu['hours_passed'];
                                                echo __('counting_down') . ' (' . $hours_left . 'h left)';
                                            } else {
                                                echo __('earning');
                                            }
                                            ?>
                                        </span>
                                    </div>
                                </div>
                                <?php if ($gpu['activation_time']): ?>
                                    <div class="activation-time">
                                        <small><?php echo __('activated_at'); ?>: <?php echo date('d.m.Y H:i', strtotime($gpu['activation_time'])); ?></small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($user_gpus) > 3): ?>
                        <a href="my_gpus.php" class="btn btn-secondary" style="margin-top: 1rem;"><?php echo __('view_all'); ?></a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="content-col">
            <div class="content-card">
                <h3><?php echo __('recent_transactions'); ?></h3>
                <?php
                $stmt = $pdo->prepare("
                    (SELECT 'investment' as type, amount, status, investment_date as date 
                     FROM investments WHERE user_id = ?)
                    UNION
                    (SELECT 'purchase' as type, amount, status, purchase_date as date 
                     FROM purchases WHERE user_id = ?)
                    ORDER BY date DESC LIMIT 5
                ");
                $stmt->execute([$user_id, $user_id]);
                $recent_transactions = $stmt->fetchAll();
                ?>
                
                <?php if (empty($recent_transactions)): ?>
                    <p class="no-data"><?php echo __('no_transactions'); ?></p>
                <?php else: ?>
                    <div class="transaction-list">
                        <?php foreach ($recent_transactions as $transaction): ?>
                            <div class="transaction-item">
                                <div class="transaction-info">
                                    <span class="transaction-type"><?php echo $transaction['type'] == 'investment' ? __('investment') : __('gpu_purchase'); ?></span>
                                    <span class="transaction-amount"><?php echo formatPrice($transaction['amount']); ?></span>
                                </div>
                                <div class="transaction-meta">
                                    <span class="transaction-status status-<?php echo $transaction['status']; ?>">
                                        <?php echo __($transaction['status']); ?>
                                    </span>
                                    <span class="transaction-date">
                                        <?php echo date('d.m.Y', strtotime($transaction['date'])); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="quick-actions">
        <h3><?php echo __('quick_actions'); ?></h3>
        <div class="action-grid">
            <a href="invest.php" class="action-card">
                <div class="action-icon">💳</div>
                <span><?php echo __('invest'); ?></span>
            </a>
            <a href="buy_gpu.php" class="action-card">
                <div class="action-icon">🖥️</div>
                <span><?php echo __('buy_gpu'); ?></span>
            </a>
            <a href="withdraw.php" class="action-card">
                <div class="action-icon">💰</div>
                <span><?php echo __('withdraw'); ?></span>
            </a>
            <a href="settings.php" class="action-card">
                <div class="action-icon">⚙️</div>
                <span><?php echo __('settings'); ?></span>
            </a>
        </div>
    </div>
</div>

<style>
.gpu-status-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}
.gpu-status-item {
    padding: 1rem;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    background: var(--bg-color);
}
.gpu-info h4 {
    margin: 0 0 0.5rem 0;
    color: var(--text-color);
}
.gpu-details {
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.gpu-income {
    font-weight: 600;
    color: var(--success-color);
}
.gpu-status {
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 600;
}
.status-waiting_activation {
    background: #f39c12;
    color: white;
}
.status-counting_down {
    background: #3498db;
    color: white;
}
.status-earning {
    background: #27ae60;
    color: white;
}
.activation-time {
    margin-top: 0.5rem;
    text-align: right;
    font-size: 0.8rem;
    color: var(--text-color);
    opacity: 0.7;
}
</style>

<?php include 'includes/footer.php'; ?>