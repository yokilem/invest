<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$page_title = __("transactions_page");

// Kullanıcı bilgilerini getir
$user = $pdo->prepare("SELECT investment_balance, username FROM users WHERE id = ?");
$user->execute([$user_id]);
$user_data = $user->fetch();

// Son işlemleri getir
$transactions = [];

// GPU Satın Alımları
$purchases = $pdo->prepare("
    SELECT 
        'purchase' as type,
        p.amount,
        p.status,
        p.purchase_date as date,
        g.name as description,
        '".__("gpu_purchase")."' as category,
        'secondary' as color
    FROM purchases p 
    JOIN gpus g ON p.gpu_id = g.id 
    WHERE p.user_id = ?
    ORDER BY p.purchase_date DESC
    LIMIT 20
");
$purchases->execute([$user_id]);
$purchase_transactions = $purchases->fetchAll();

// Yatırımlar
$investments = $pdo->prepare("
    SELECT 
        'investment' as type,
        amount,
        status,
        investment_date as date,
        '".__("investment")."' as description,
        '".__("investment")."' as category,
        'success' as color
    FROM investments 
    WHERE user_id = ?
    ORDER BY investment_date DESC
    LIMIT 20
");
$investments->execute([$user_id]);
$investment_transactions = $investments->fetchAll();

// Çekimler
try {
    $withdrawals = $pdo->prepare("
        SELECT 
            'withdrawal' as type,
            amount,
            status,
            created_at as date,
            CONCAT('".__("withdraw")." - ', coin_type) as description,
            '".__("withdraw")."' as category,
            'warning' as color
        FROM withdrawals 
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 20
    ");
    $withdrawals->execute([$user_id]);
    $withdrawal_transactions = $withdrawals->fetchAll();
} catch (Exception $e) {
    $withdrawal_transactions = [];
}

// Tüm işlemleri birleştir ve tarihe göre sırala
$all_transactions = array_merge($purchase_transactions, $investment_transactions, $withdrawal_transactions);
usort($all_transactions, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

// İstatistikler
$total_investment = $pdo->prepare("SELECT SUM(amount) as total FROM investments WHERE user_id = ? AND status = 'approved'");
$total_investment->execute([$user_id]);
$total_investment_amount = $total_investment->fetch()['total'] ?: 0;

$total_withdrawal = $pdo->prepare("SELECT SUM(amount) as total FROM withdrawals WHERE user_id = ? AND status = 'approved'");
$total_withdrawal->execute([$user_id]);
$total_withdrawal_amount = $total_withdrawal->fetch()['total'] ?: 0;

$pending_transactions = $pdo->prepare("
    SELECT COUNT(*) as count FROM (
        SELECT id FROM investments WHERE user_id = ? AND status = 'pending'
        UNION ALL
        SELECT id FROM purchases WHERE user_id = ? AND status = 'pending'
        UNION ALL
        SELECT id FROM withdrawals WHERE user_id = ? AND status = 'pending'
    ) as pending
");
$pending_transactions->execute([$user_id, $user_id, $user_id]);
$pending_count = $pending_transactions->fetch()['count'];
?>
<?php include 'includes/header.php'; ?>

<div class="page-header">
    <h1><?php echo __("transactions_page"); ?></h1>
    <p><?php echo __("transactions_description"); ?></p>
</div>

<!-- İstatistik Kartları -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon">💰</div>
        <div class="stat-info">
            <h3><?php echo __("yatırım_bakiyesi"); ?></h3>
            <p class="stat-value"><?php echo formatPrice($user_data['investment_balance']); ?></p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">📈</div>
        <div class="stat-info">
            <h3><?php echo __("total_investment"); ?></h3>
            <p class="stat-value"><?php echo formatPrice($total_investment_amount); ?></p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">📤</div>
        <div class="stat-info">
            <h3><?php echo __("total_withdrawal"); ?></h3>
            <p class="stat-value"><?php echo formatPrice($total_withdrawal_amount); ?></p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">⏳</div>
        <div class="stat-info">
            <h3><?php echo __("pending_transactions"); ?></h3>
            <p class="stat-value"><?php echo $pending_count; ?></p>
        </div>
    </div>
</div>

<div class="content-row">
    <div class="content-col">
        <!-- Tüm İşlemler -->
        <div class="content-card">
            <div class="card-header">
                <h3>📊 <?php echo __("all_transactions"); ?></h3>
                <div class="card-actions">
                    <button class="btn btn-sm btn-secondary" onclick="filterTransactions('all')"><?php echo __("all"); ?></button>
                    <button class="btn btn-sm btn-secondary" onclick="filterTransactions('pending')"><?php echo __("pending"); ?></button>
                    <button class="btn btn-sm btn-secondary" onclick="filterTransactions('approved')"><?php echo __("approved"); ?></button>
                </div>
            </div>
            
            <div class="transactions-list" id="transactionsList">
                <?php if (empty($all_transactions)): ?>
                    <div class="no-data">
                        <p><?php echo __("no_transactions"); ?></p>
                        <a href="buy_gpu.php" class="btn btn-primary"><?php echo __("buy_first_gpu"); ?></a>
                    </div>
                <?php else: ?>
                    <?php foreach ($all_transactions as $transaction): 
                        $status_class = 'status-' . $transaction['status'];
                        $color_class = 'color-' . $transaction['color'];
                        $type_icon = getTransactionIcon($transaction['type']);
                    ?>
                        <div class="transaction-item <?php echo $status_class; ?> <?php echo $color_class; ?>" data-status="<?php echo $transaction['status']; ?>">
                            <div class="transaction-icon">
                                <?php echo $type_icon; ?>
                            </div>
                            <div class="transaction-info">
                                <div class="transaction-main">
                                    <strong><?php echo $transaction['description']; ?></strong>
                                    <span class="transaction-amount <?php echo $transaction['type'] === 'withdrawal' ? 'negative' : 'positive'; ?>">
                                        <?php echo $transaction['type'] === 'withdrawal' ? '-' : '+'; ?>
                                        <?php echo formatPrice($transaction['amount']); ?>
                                    </span>
                                </div>
                                <div class="transaction-meta">
                                    <span class="transaction-category"><?php echo $transaction['category']; ?></span>
                                    <span class="transaction-date"><?php echo date('d.m.Y H:i', strtotime($transaction['date'])); ?></span>
                                    <span class="transaction-status <?php echo $status_class; ?>">
                                        <?php echo getStatusText($transaction['status']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="content-col">
        <!-- İşlem Özeti -->
        <div class="content-card">
            <h3>📋 <?php echo __("transaction_summary"); ?></h3>
            <div class="summary-stats">
                <div class="summary-item">
                    <span class="summary-label"><?php echo __("total_transactions"); ?>:</span>
                    <span class="summary-value"><?php echo count($all_transactions); ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label"><?php echo __("approved"); ?>:</span>
                    <span class="summary-value success"><?php echo count(array_filter($all_transactions, function($t) { return $t['status'] === 'approved'; })); ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label"><?php echo __("pending"); ?>:</span>
                    <span class="summary-value warning"><?php echo count(array_filter($all_transactions, function($t) { return $t['status'] === 'pending'; })); ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label"><?php echo __("rejected"); ?>:</span>
                    <span class="summary-value error"><?php echo count(array_filter($all_transactions, function($t) { return $t['status'] === 'rejected'; })); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Son Yatırımlar -->
        <div class="content-card">
            <h3>💳 <?php echo __("my_investments"); ?></h3>
            <div class="mini-list">
                <?php if (empty($investment_transactions)): ?>
                    <p class="no-data-mini"><?php echo __("no_transactions"); ?></p>
                <?php else: ?>
                    <?php foreach (array_slice($investment_transactions, 0, 5) as $investment): ?>
                        <div class="mini-item">
                            <div class="mini-icon">💰</div>
                            <div class="mini-info">
                                <strong><?php echo formatPrice($investment['amount']); ?></strong>
                                <span class="mini-date"><?php echo date('d.m.Y', strtotime($investment['date'])); ?></span>
                            </div>
                            <div class="mini-status status-<?php echo $investment['status']; ?>">
                                <?php echo getStatusText($investment['status']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (count($investment_transactions) > 5): ?>
                        <a href="invest.php" class="btn btn-secondary btn-sm btn-block"><?php echo __("view_all"); ?></a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Son Çekimler -->
        <div class="content-card">
            <h3>📤 <?php echo __("my_withdrawals"); ?></h3>
            <div class="mini-list">
                <?php if (empty($withdrawal_transactions)): ?>
                    <p class="no-data-mini"><?php echo __("no_transactions"); ?></p>
                <?php else: ?>
                    <?php foreach (array_slice($withdrawal_transactions, 0, 5) as $withdrawal): ?>
                        <div class="mini-item">
                            <div class="mini-icon">💸</div>
                            <div class="mini-info">
                                <strong><?php echo formatPrice($withdrawal['amount']); ?></strong>
                                <span class="mini-date"><?php echo date('d.m.Y', strtotime($withdrawal['date'])); ?></span>
                            </div>
                            <div class="mini-status status-<?php echo $withdrawal['status']; ?>">
                                <?php echo getStatusText($withdrawal['status']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (count($withdrawal_transactions) > 5): ?>
                        <a href="withdraw.php" class="btn btn-secondary btn-sm btn-block"><?php echo __("view_all"); ?></a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Hızlı İşlemler -->
        <div class="content-card">
            <h3>⚡ <?php echo __("quick_transactions"); ?></h3>
            <div class="quick-actions-grid">
                <a href="invest.php" class="quick-action">
                    <div class="action-icon">💰</div>
                    <span><?php echo __("invest"); ?></span>
                </a>
                <a href="withdraw.php" class="quick-action">
                    <div class="action-icon">📤</div>
                    <span><?php echo __("withdraw"); ?></span>
                </a>
                <a href="buy_gpu.php" class="quick-action">
                    <div class="action-icon">🖥️</div>
                    <span><?php echo __("buy_gpu"); ?></span>
                </a>
                <a href="my_gpus.php" class="quick-action">
                    <div class="action-icon">📊</div>
                    <span><?php echo __("my_gpus"); ?></span>
                </a>
            </div>
        </div>
    </div>
</div>

<?php
// Yardımcı fonksiyonlar
function getTransactionIcon($type) {
    switch ($type) {
        case 'purchase': return '🖥️';
        case 'investment': return '💰';
        case 'withdrawal': return '📤';
        default: return '📄';
    }
}

function getStatusText($status) {
    switch ($status) {
        case 'pending': return __("pending");
        case 'approved': return __("approved");
        case 'rejected': return __("rejected");
        default: return $status;
    }
}
?>

<script>
function filterTransactions(status) {
    const items = document.querySelectorAll('.transaction-item');
    
    items.forEach(item => {
        if (status === 'all') {
            item.style.display = 'flex';
        } else {
            if (item.getAttribute('data-status') === status) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        }
    });
    
    // Butonları aktif/pasif yap
    document.querySelectorAll('.card-actions .btn').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');
}
</script>

<?php include 'includes/footer.php'; ?>