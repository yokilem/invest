<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$page_title = __("invest_page");
$success = '';
$error = '';

// Yatırım işlemi
if ($_POST && isset($_POST['amount'])) {
    $amount = floatval($_POST['amount']);
    $payment_method_id = intval($_POST['payment_method']);
    
    // Minimum yatırım kontrolü
    $min_investment = getSetting('min_investment', 500);
    
    if ($amount < $min_investment) {
        $error = __('minimum_investment') . ': ' . formatPrice($min_investment);
    } elseif (empty($_FILES['screenshot']['name'])) {
        $error = __('screenshot_required');
    } else {
        // Dosya yükleme
        $upload_result = uploadFile($_FILES['screenshot'], 'screenshots');
        
        if ($upload_result['success']) {
            try {
                // Yatırım kaydı oluştur
                $stmt = $pdo->prepare("
                    INSERT INTO investments (user_id, amount, screenshot_path, payment_method_id, status) 
                    VALUES (?, ?, ?, ?, 'pending')
                ");
                
                if ($stmt->execute([$user_id, $amount, $upload_result['file_path'], $payment_method_id])) {
                    $success = __('investment_request_received');
                } else {
                    $error = __('operation_failed');
                }
            } catch (Exception $e) {
                $error = __('operation_failed') . $e->getMessage();
            }
        } else {
            $error = $upload_result['error'];
        }
    }
}

// Yatırım bakiyesi
$investment_balance = getUserInvestmentBalance($user_id);
$earnings_balance = getUserEarningsBalance($user_id);

// Ödeme yöntemleri
$payment_methods = getPaymentMethods();

// Son yatırımlar
$recent_investments = [];
try {
    $stmt = $pdo->prepare("
        SELECT * FROM investments 
        WHERE user_id = ? 
        ORDER BY investment_date DESC 
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $recent_investments = $stmt->fetchAll();
} catch (Exception $e) {
    $recent_investments = [];
}
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['language']; ?>" data-theme="<?php echo getCurrentTheme(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo __('site_title'); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .wallet-address-display {
            background: var(--bg-color);
            padding: 12px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            font-family: monospace;
            word-break: break-all;
            margin-bottom: 8px;
            font-size: 12px;
        }
        .copy-btn {
            margin-top: 5px;
        }
        .balance-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .balance-item {
            text-align: center;
            padding: 1rem;
            border-radius: 8px;
            color: white;
        }
        .investment-balance {
            background: linear-gradient(135deg, #3498db, #2980b9);
        }
        .earnings-balance {
            background: linear-gradient(135deg, #27ae60, #219652);
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container">
        <div class="page-header">
            <h1><?php echo __("invest_page"); ?></h1>
            <p><?php echo __("invest_description"); ?></p>
        </div>

        <div class="content-row">
            <div class="content-col">
                <div class="content-card">
                    <h3><?php echo __('investment_info'); ?></h3>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-error"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <!-- Bakiye Bilgileri -->
                    <div class="balance-grid">
                        <div class="balance-item investment-balance">
                            <div style="font-size: 0.9rem; opacity: 0.9;">Yatırım Bakiyesi</div>
                            <div style="font-size: 1.5rem; font-weight: bold;"><?php echo formatPrice($investment_balance); ?></div>
                            <small style="opacity: 0.8;">GPU satın almak için</small>
                        </div>
                        <div class="balance-item earnings-balance">
                            <div style="font-size: 0.9rem; opacity: 0.9;">Kazanç Bakiyesi</div>
                            <div style="font-size: 1.5rem; font-weight: bold;"><?php echo formatPrice($earnings_balance); ?></div>
                            <small style="opacity: 0.8;">Para çekmek için</small>
                        </div>
                    </div>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="amount"><?php echo __('investment_amount'); ?>:</label>
                            <input type="number" id="amount" name="amount" required 
                                   min="<?php echo getSetting('min_investment', 500); ?>" step="50"
                                   placeholder="<?php echo __('minimum_investment'); ?> <?php echo formatPrice(getSetting('min_investment', 500)); ?>"
                                   value="<?php echo isset($_POST['amount']) ? htmlspecialchars($_POST['amount']) : ''; ?>">
                            <small><?php echo __('minimum_investment'); ?>: <?php echo formatPrice(getSetting('min_investment', 500)); ?></small>
                        </div>
                        
                        <div class="form-group">
                            <label for="payment_method"><?php echo __('payment_method'); ?>:</label>
                            <select name="payment_method" id="payment_method" required>
                                <option value=""><?php echo __('select_payment_method'); ?></option>
                                <?php foreach ($payment_methods as $method): ?>
                                    <option value="<?php echo $method['id']; ?>" <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] == $method['id']) ? 'selected' : ''; ?>>
                                        <?php echo $method['coin_name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Cüzdan Adresi Gösterimi -->
                        <div class="form-group">
                            <label><?php echo __('wallet_address'); ?>:</label>
                            <div id="walletAddress" class="wallet-address-display">
                                <?php echo __('select_payment_method_first'); ?>
                            </div>
                            <button type="button" id="copyAddress" class="btn btn-secondary btn-sm copy-btn" style="display: none;">
                                📋 <?php echo __('copy_address'); ?>
                            </button>
                        </div>
                        
                        <div class="form-group">
                            <label for="screenshot"><?php echo __('payment_screenshot'); ?>:</label>
                            <input type="file" name="screenshot" id="screenshot" accept="image/*" required>
                            <small><?php echo __('file_requirements'); ?></small>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <?php echo __('confirm_purchase'); ?>
                            </button>
                            <a href="dashboard.php" class="btn btn-secondary"><?php echo __('cancel'); ?></a>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="content-col">
                <div class="content-card">
                    <h3><?php echo __('investment_rules'); ?></h3>
                    <div class="rules-list">
                        <div class="rule-item">
                            <div class="rule-icon">💰</div>
                            <div class="rule-content">
                                <h4><?php echo __('minimum_investment'); ?></h4>
                                <p><?php echo __('minimum_investment'); ?>: <?php echo formatPrice(getSetting('min_investment', 500)); ?></p>
                            </div>
                        </div>
                        
                        <div class="rule-item">
                            <div class="rule-icon">⏱️</div>
                            <div class="rule-content">
                                <h4><?php echo __('processing_time'); ?></h4>
                                <p><?php echo __('approval_time_desc'); ?></p>
                            </div>
                        </div>
                        
                        <div class="rule-item">
                            <div class="rule-icon">📱</div>
                            <div class="rule-content">
                                <h4><?php echo __('support'); ?></h4>
                                <p><?php echo __('contact_support'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="content-card">
                    <h3><?php echo __('my_investments'); ?></h3>
                    <?php if (empty($recent_investments)): ?>
                        <p class="no-data"><?php echo __('no_transactions'); ?></p>
                    <?php else: ?>
                        <div class="investment-list">
                            <?php foreach ($recent_investments as $investment): ?>
                                <div class="investment-item">
                                    <div class="investment-amount"><?php echo formatPrice($investment['amount']); ?></div>
                                    <div class="investment-info">
                                        <div class="investment-date"><?php echo date('d.m.Y H:i', strtotime($investment['investment_date'])); ?></div>
                                        <div class="investment-status status-<?php echo $investment['status']; ?>">
                                            <?php 
                                            $status_text = [
                                                'pending' => __('pending'),
                                                'approved' => __('approved'),
                                                'rejected' => __('rejected')
                                            ];
                                            echo $status_text[$investment['status']] ?? $investment['status'];
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script>
    const paymentMethods = <?php echo json_encode($payment_methods); ?>;

    document.getElementById('payment_method').addEventListener('change', function() {
        const methodId = this.value;
        const method = paymentMethods.find(m => m.id == methodId);
        const walletDiv = document.getElementById('walletAddress');
        const copyBtn = document.getElementById('copyAddress');
        
        if (method) {
            walletDiv.innerHTML = '<code style="word-break: break-all;">' + method.wallet_address + '</code>';
            copyBtn.style.display = 'inline-block';
        } else {
            walletDiv.innerHTML = '<?php echo __('select_payment_method_first'); ?>';
            copyBtn.style.display = 'none';
        }
    });

    document.getElementById('copyAddress').addEventListener('click', function() {
        const methodId = document.getElementById('payment_method').value;
        const method = paymentMethods.find(m => m.id == methodId);
        
        if (method) {
            navigator.clipboard.writeText(method.wallet_address).then(() => {
                alert('Cüzdan adresi kopyalandı!');
            }).catch(() => {
                alert('Kopyalama başarısız!');
            });
        } else {
            alert('Önce ödeme yöntemi seçin!');
        }
    });
    </script>

    <?php include 'includes/footer.php'; ?>
</body>
</html>