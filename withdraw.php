<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$page_title = __("withdraw");
$success = '';
$error = '';

// 24 saat dolmuş GPU'lar için kazanç hesapla (kullanıcı sayfayı ziyaret ettiğinde)
calculate24HourEarnings($user_id);

// Kullanıcının KAZANÇ bakiyesi (sadece para çekme için)
$user_earnings_balance = getUserEarningsBalance($user_id);

// Minimum çekim tutarını site ayarlarından al
$min_withdrawal = getSetting('min_withdrawal', 75);

// Çekim işlemi
if ($_POST && isset($_POST['amount'])) {
    $amount = floatval($_POST['amount']);
    $wallet_address = trim($_POST['wallet_address']);
    $coin_type = $_POST['coin_type'];
    
    if ($amount <= 0) {
        $error = __('amount_must_be_positive');
    } elseif ($amount > $user_earnings_balance) {
        $error = __('insufficient_balance') . ' ' . formatPrice($user_earnings_balance);
    } elseif ($amount < $min_withdrawal) {
        $error = __('minimum_withdrawal') . ': ' . formatPrice($min_withdrawal);
    } elseif (empty($wallet_address)) {
        $error = __('wallet_address_required');
    } elseif (empty($coin_type)) {
        $error = __('currency_required');
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO withdrawals (user_id, amount, wallet_address, coin_type, status) 
                VALUES (?, ?, ?, ?, 'pending')
            ");
            
            if ($stmt->execute([$user_id, $amount, $wallet_address, $coin_type])) {
                // KAZANÇ bakiyesinden düş
                $new_balance = $user_earnings_balance - $amount;
                $update_stmt = $pdo->prepare("UPDATE users SET earnings_balance = ? WHERE id = ?");
                $update_stmt->execute([$new_balance, $user_id]);
                
                $success = __('withdrawal_request_received');
                $user_earnings_balance = $new_balance; // Güncel bakiyeyi göster
            } else {
                $error = __('operation_failed');
            }
        } catch (Exception $e) {
            $error = __('operation_failed') . ': ' . $e->getMessage();
        }
    }
}

// Güncel kazanç bakiyesini tekrar al
$user_earnings_balance = getUserEarningsBalance($user_id);
$user_investment_balance = getUserInvestmentBalance($user_id);
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['language']; ?>" data-theme="<?php echo getCurrentTheme(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo __('site_title'); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .balance-display {
            text-align: center;
            padding: 2rem;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 12px;
            margin-bottom: 2rem;
        }
        .balance-label {
            font-size: 1rem;
            opacity: 0.9;
            margin-bottom: 0.5rem;
        }
        .balance-amount {
            font-size: 2.5rem;
            font-weight: bold;
        }
        .withdrawal-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        .withdrawal-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: var(--bg-color);
            border-radius: 8px;
            border-left: 4px solid var(--success-color);
        }
        .withdrawal-amount {
            font-weight: bold;
            font-size: 1.1rem;
            color: var(--success-color);
        }
        .withdrawal-info {
            text-align: right;
        }
        .withdrawal-date {
            font-size: 0.9rem;
            color: var(--text-color);
            opacity: 0.7;
        }
        .withdrawal-status {
            font-size: 0.8rem;
            font-weight: 600;
            margin-top: 0.25rem;
        }
        .rules-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .rule-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1rem;
            background: var(--bg-color);
            border-radius: 8px;
        }
        .rule-icon {
            font-size: 1.5rem;
            flex-shrink: 0;
        }
        .rule-content h4 {
            margin: 0 0 0.25rem 0;
            color: var(--text-color);
        }
        .rule-content p {
            margin: 0;
            color: var(--text-color);
            opacity: 0.8;
            font-size: 0.9rem;
        }
        
        /* Bakiye grid */
        .balance-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .balance-item {
            text-align: center;
            padding: 1.5rem;
            border-radius: 8px;
            color: white;
        }
        .investment-balance {
            background: linear-gradient(135deg, #3498db, #2980b9);
        }
        .earnings-balance {
            background: linear-gradient(135deg, #27ae60, #219652);
        }
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        .form-actions .btn {
            flex: 1;
            padding: 12px 20px;
            font-size: 1rem;
            height: auto;
        }
        .btn-disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .validation-message {
            display: none;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        .validation-error {
            background: #ffe6e6;
            color: #d63031;
            border: 1px solid #ff7675;
        }
        .validation-success {
            background: #e6f7e6;
            color: #27ae60;
            border: 1px solid #27ae60;
        }
        .amount-input-container {
            position: relative;
        }
        .amount-suggestions {
            display: flex;
            gap: 5px;
            margin-top: 5px;
            flex-wrap: wrap;
        }
        .amount-suggestion {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .amount-suggestion:hover {
            background: #e9ecef;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container">
        <div class="page-header">
            <h1><?php echo __("withdraw"); ?></h1>
            <p><?php echo __('withdraw_earnings'); ?></p>
        </div>

        <div class="content-row">
            <div class="content-col">
                <div class="content-card">
                    <h3><?php echo __('withdrawal_amount'); ?></h3>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-error"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <!-- Bakiye Bilgileri -->
                    <div class="balance-grid">
                        <div class="balance-item investment-balance">
                            <div style="font-size: 0.9rem; opacity: 0.9;"><?php echo __('investment_balance'); ?></div>
                            <div style="font-size: 1.5rem; font-weight: bold;"><?php echo formatPrice($user_investment_balance); ?></div>
                            <small style="opacity: 0.8;"><?php echo __('for_buying_gpu'); ?></small>
                        </div>
                        <div class="balance-item earnings-balance">
                            <div style="font-size: 0.9rem; opacity: 0.9;"><?php echo __('available_balance'); ?></div>
                            <div style="font-size: 1.5rem; font-weight: bold;"><?php echo formatPrice($user_earnings_balance); ?></div>
                            <small style="opacity: 0.8;"><?php echo __('for_withdrawal'); ?></small>
                        </div>
                    </div>
                    
                    <form method="POST" id="withdrawalForm">
                        <div class="form-group">
                            <label for="amount"><?php echo __('withdrawal_amount'); ?>:</label>
                            <div class="amount-input-container">
                                <input type="number" id="amount" name="amount" required 
                                       min="<?php echo $min_withdrawal; ?>" step="10" 
                                       placeholder="<?php echo __('minimum_withdrawal'); ?> <?php echo formatPrice($min_withdrawal); ?>"
                                       max="<?php echo $user_earnings_balance; ?>"
                                       value="<?php echo isset($_POST['amount']) ? htmlspecialchars($_POST['amount']) : ''; ?>"
                                       class="amount-input">
                                <div class="amount-suggestions">
                                    <?php
                                    $suggestions = [
                                        $min_withdrawal,
                                        $min_withdrawal * 2,
                                        $min_withdrawal * 5,
                                        $user_earnings_balance
                                    ];
                                    $suggestions = array_unique($suggestions);
                                    sort($suggestions);
                                    
                                    foreach ($suggestions as $suggestion):
                                        if ($suggestion <= $user_earnings_balance && $suggestion >= $min_withdrawal):
                                    ?>
                                        <div class="amount-suggestion" data-amount="<?php echo $suggestion; ?>">
                                            <?php echo formatPrice($suggestion); ?>
                                        </div>
                                    <?php endif; endforeach; ?>
                                </div>
                            </div>
                            <small><?php echo __('minimum_withdrawal'); ?>: <?php echo formatPrice($min_withdrawal); ?></small>
                        </div>
                        
                        <div class="form-group">
                            <label for="wallet_address"><?php echo __('wallet_address'); ?>:</label>
                            <input type="text" id="wallet_address" name="wallet_address" required 
                                   placeholder="<?php echo __('enter_wallet_or_account'); ?>"
                                   value="<?php echo isset($_POST['wallet_address']) ? htmlspecialchars($_POST['wallet_address']) : ''; ?>">
                            <small><?php echo __('enter_wallet_or_account'); ?></small>
                        </div>
                        
                        <div class="form-group">
                            <label for="coin_type"><?php echo __('currency'); ?>:</label>
                            <select name="coin_type" id="coin_type" required>
                                <option value=""><?php echo __('select'); ?></option>
                                <option value="BTC" <?php echo (isset($_POST['coin_type']) && $_POST['coin_type'] == 'BTC') ? 'selected' : ''; ?>>Bitcoin (BTC)</option>
                                <option value="ETH" <?php echo (isset($_POST['coin_type']) && $_POST['coin_type'] == 'ETH') ? 'selected' : ''; ?>>Ethereum (ETH)</option>
                                <option value="USDT" <?php echo (isset($_POST['coin_type']) && $_POST['coin_type'] == 'USDT') ? 'selected' : ''; ?>>Tether (USDT)</option>
                                <option value="TRX" <?php echo (isset($_POST['coin_type']) && $_POST['coin_type'] == 'TRX') ? 'selected' : ''; ?>>TRON (TRX)</option>
                                <option value="LTC" <?php echo (isset($_POST['coin_type']) && $_POST['coin_type'] == 'LTC') ? 'selected' : ''; ?>>Litecoin (LTC)</option>
                            </select>
                        </div>
                        
                        <!-- Validasyon Mesajları -->
                        <div id="validationMessage" class="validation-message"></div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <?php echo __('request_withdrawal'); ?>
                            </button>
                            <a href="dashboard.php" class="btn btn-secondary"><?php echo __('cancel'); ?></a>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="content-col">
                <div class="content-card">
                    <h3><?php echo __('withdrawal_limits'); ?></h3>
                    <div class="rules-list">
                        <div class="rule-item">
                            <div class="rule-icon">💰</div>
                            <div class="rule-content">
                                <h4><?php echo __('minimum_withdrawal'); ?></h4>
                                <p><?php echo __('minimum_withdrawal'); ?> <?php echo formatPrice($min_withdrawal); ?></p>
                            </div>
                        </div>
                        
                        <div class="rule-item">
                            <div class="rule-icon">⏱️</div>
                            <div class="rule-content">
                                <h4><?php echo __('processing_time'); ?></h4>
                                <p><?php echo __('processing_time'); ?> 1-3 <?php echo __('business_days'); ?></p>
                            </div>
                        </div>
                        
                        <div class="rule-item">
                            <div class="rule-icon">🏦</div>
                            <div class="rule-content">
                                <h4><?php echo __('commission_rate'); ?></h4>
                                <p><?php echo __('commission_rate'); ?> %0</p>
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
                    <h3><?php echo __('withdrawal_history'); ?></h3>
                    <?php
                    try {
                        $stmt = $pdo->prepare("
                            SELECT * FROM withdrawals 
                            WHERE user_id = ? 
                            ORDER BY created_at DESC 
                            LIMIT 5
                        ");
                        $stmt->execute([$user_id]);
                        $recent_withdrawals = $stmt->fetchAll();
                    } catch (Exception $e) {
                        $recent_withdrawals = [];
                    }
                    ?>
                    
                    <?php if (empty($recent_withdrawals)): ?>
                        <p class="no-data"><?php echo __('no_withdrawals_found'); ?></p>
                    <?php else: ?>
                        <div class="withdrawal-list">
                            <?php foreach ($recent_withdrawals as $withdrawal): ?>
                                <div class="withdrawal-item">
                                    <div class="withdrawal-amount"><?php echo formatPrice($withdrawal['amount']); ?></div>
                                    <div class="withdrawal-info">
                                        <div class="withdrawal-date"><?php echo date('d.m.Y H:i', strtotime($withdrawal['created_at'])); ?></div>
                                        <div class="withdrawal-status status-<?php echo $withdrawal['status']; ?>">
                                            <?php 
                                            $status_text = [
                                                'pending' => __('pending'),
                                                'approved' => __('approved'),
                                                'rejected' => __('rejected')
                                            ];
                                            echo $status_text[$withdrawal['status']] ?? $withdrawal['status'];
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
    document.addEventListener('DOMContentLoaded', function() {
        const amountInput = document.getElementById('amount');
        const walletInput = document.getElementById('wallet_address');
        const coinSelect = document.getElementById('coin_type');
        const submitBtn = document.getElementById('submitBtn');
        const validationMessage = document.getElementById('validationMessage');
        const earningsBalance = <?php echo $user_earnings_balance; ?>;
        const minWithdrawal = <?php echo $min_withdrawal; ?>;

        // Miktar önerilerine tıklama
        document.querySelectorAll('.amount-suggestion').forEach(suggestion => {
            suggestion.addEventListener('click', function() {
                amountInput.value = this.getAttribute('data-amount');
                validateForm();
            });
        });

        // Form validasyonu
        function validateForm() {
            const amount = parseFloat(amountInput.value) || 0;
            const wallet = walletInput.value.trim();
            const coin = coinSelect.value;
            
            // Mesajı temizle
            validationMessage.style.display = 'none';
            validationMessage.className = 'validation-message';
            
            // Buton durumunu sıfırla
            submitBtn.disabled = false;
            submitBtn.classList.remove('btn-disabled');
            
            // Validasyon kontrolleri
            if (amount <= 0) {
                showValidation('<?php echo __('amount_must_be_positive'); ?>', 'error');
                disableButton();
                return false;
            }
            
            if (amount > earningsBalance) {
                showValidation('<?php echo __('insufficient_balance'); ?> ' + formatCurrency(earningsBalance), 'error');
                disableButton();
                return false;
            }
            
            if (amount < minWithdrawal) {
                showValidation('<?php echo __('minimum_withdrawal'); ?>: ' + formatCurrency(minWithdrawal), 'error');
                disableButton();
                return false;
            }
            
            if (!wallet) {
                showValidation('<?php echo __('wallet_address_required'); ?>', 'error');
                disableButton();
                return false;
            }
            
            if (!coin) {
                showValidation('<?php echo __('currency_required'); ?>', 'error');
                disableButton();
                return false;
            }
            
            // Geçerli durum
            const remaining = earningsBalance - amount;
            showValidation('✅ <?php echo __('valid_withdrawal_amount'); ?>. <?php echo __('remaining_balance'); ?>: ' + formatCurrency(remaining), 'success');
            return true;
        }

        function showValidation(message, type) {
            validationMessage.textContent = message;
            validationMessage.className = `validation-message validation-${type}`;
            validationMessage.style.display = 'block';
        }

        function disableButton() {
            submitBtn.disabled = true;
            submitBtn.classList.add('btn-disabled');
        }

        function formatCurrency(amount) {
            return '$' + amount.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
        }

        // Input değişikliklerinde validasyon
        amountInput.addEventListener('input', validateForm);
        walletInput.addEventListener('input', validateForm);
        coinSelect.addEventListener('change', validateForm);

        // Form gönderimi
        document.getElementById('withdrawalForm').addEventListener('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
                return false;
            }
            
            // Butonu devre dışı bırak ve yükleme göstergesi göster
            submitBtn.disabled = true;
            submitBtn.innerHTML = '⏳ <?php echo __('processing'); ?>...';
        });

        // Sayfa yüklendiğinde validasyon yap
        validateForm();
    });
    </script>

    <?php include 'includes/footer.php'; ?>
</body>
</html>