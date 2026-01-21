<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$page_title = __("buy_gpu");

// Başarı/error mesajlarını kontrol et ve TEMİZLE
$success = isset($_SESSION['success']) ? $_SESSION['success'] : '';
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['success']);
unset($_SESSION['error']);

// GPU listesini getir
$gpus = getGPUs();

// Kullanıcının yatırım bakiyesini getir
$user_id = $_SESSION['user_id'];
$user_investment_balance = getUserInvestmentBalance($user_id);
$user_earnings_balance = getUserEarningsBalance($user_id);
?>
<?php include 'includes/header.php'; ?>

<div class="page-header">
    <h1><?php echo __("buy_gpu"); ?></h1>
    <p><?php echo __('purchase_new_gpu'); ?></p>
    
    <!-- Bakiye Bilgileri -->
    <div class="balance-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1.5rem; max-width: 500px;">
        <!-- YENİ KOD - DİL DESTEKLİ -->
<div class="balance-item" style="text-align: center; padding: 1rem; background: linear-gradient(135deg, #3498db, #2980b9); color: white; border-radius: 8px;">
    <div style="font-size: 0.9rem; opacity: 0.9;"><?php echo __('investment_balance'); ?></div>
    <div style="font-size: 1.3rem; font-weight: bold;"><?php echo formatPrice($user_investment_balance); ?></div>
    <small style="opacity: 0.8;"><?php echo __('for_buying_gpu'); ?></small>
</div>
<div class="balance-item" style="text-align: center; padding: 1rem; background: linear-gradient(135deg, #27ae60, #219652); color: white; border-radius: 8px;">
    <div style="font-size: 0.9rem; opacity: 0.9;"><?php echo __('earnings_balance'); ?></div>
    <div style="font-size: 1.3rem; font-weight: bold;"><?php echo formatPrice($user_earnings_balance); ?></div>
    <small style="opacity: 0.8;"><?php echo __('for_withdrawal'); ?></small>
</div>

<?php if ($user_investment_balance == 0): ?>
<div class="alert alert-warning" style="margin-top: 1rem;">
    💡 <strong><?php echo __('no_investment_balance'); ?></strong> <?php echo __('investment_required_message'); ?> <a href="invest.php" style="color: #fff; text-decoration: underline;"><?php echo __('make_investment'); ?></a>.
</div>
<?php endif; ?>
</div>

<!-- Bildirimler -->
<?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?php echo $error; ?></div>
<?php endif; ?>

<div class="gpu-grid detailed">
    <?php foreach ($gpus as $gpu): 
        $dailyEarning = calculateDailyEarning($gpu['monthly_income']);
        $yearlyEarning = calculateYearlyIncome($gpu['monthly_income']);
        $returnRate = calculateReturnRate($gpu['price'], $gpu['monthly_income']);
        $can_afford = $user_investment_balance >= $gpu['price'];
    ?>
        <div class="gpu-card detailed">
            <div class="gpu-image-container">
                <img src="<?php echo $gpu['image_path'] ?: 'https://via.placeholder.com/300x200/3498db/ffffff?text=GPU+Image'; ?>" 
                     alt="<?php echo $gpu['name']; ?>" 
                     class="gpu-image">
                <?php if ($gpu['stock'] < 10 && $gpu['stock'] > 0): ?>
                    <div class="stock-badge"><?php echo __('last_stock'); ?> <?php echo $gpu['stock']; ?> <?php echo __('quantity'); ?></div>
                <?php endif; ?>
            </div>
            
            <div class="gpu-info">
                <h3><?php echo $gpu['name']; ?></h3>
                <p class="gpu-description"><?php echo $gpu['description']; ?></p>
                
                <div class="gpu-specs">
                    <div class="spec-item">
                        <span class="spec-label"><?php echo __('price'); ?>:</span>
                        <span class="spec-value"><?php echo formatPrice($gpu['price']); ?></span>
                    </div>
                    <div class="spec-item">
                        <span class="spec-label"><?php echo __('daily_income'); ?>:</span>
                        <span class="spec-value"><?php echo formatPrice($dailyEarning); ?></span>
                    </div>
                    <div class="spec-item">
                        <span class="spec-label"><?php echo __('monthly_income'); ?>:</span>
                        <span class="spec-value"><?php echo formatPrice($gpu['monthly_income']); ?></span>
                    </div>
                    <div class="spec-item">
                        <span class="spec-label"><?php echo __('yearly_income'); ?>:</span>
                        <span class="spec-value"><?php echo formatPrice($yearlyEarning); ?></span>
                    </div>
                    <div class="spec-item">
                        <span class="spec-label"><?php echo __('return_rate'); ?>:</span>
                        <span class="spec-value">%<?php echo number_format($returnRate, 2); ?></span>
                    </div>
                    <div class="spec-item">
                        <span class="spec-label"><?php echo __('stock'); ?>:</span>
                        <span class="spec-value"><?php echo $gpu['stock']; ?> <?php echo __('quantity'); ?></span>
                    </div>
                    
                    <!-- Yatırım Bakiyesi Kontrolü -->
                    <div class="spec-item" style="border-top: 2px solid <?php echo $can_afford ? '#27ae60' : '#e74c3c'; ?>;">
                        <span class="spec-label">Durum:</span>
                        <span class="spec-value" style="color: <?php echo $can_afford ? '#27ae60' : '#e74c3c'; ?>; font-weight: bold;">
                            <?php if ($can_afford): ?>
                                ✅ Satın alınabilir
                            <?php else: ?>
                                ❌ Yetersiz bakiye
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="gpu-actions">
                <?php if ($gpu['stock'] > 0): ?>
                    <?php if ($can_afford): ?>
                        <button class="btn btn-primary btn-block" onclick="openPurchaseModal(<?php echo $gpu['id']; ?>)">
                            <?php echo __('buy_now'); ?>
                        </button>
                    <?php else: ?>
                        <button class="btn btn-secondary btn-block" disabled title="Yetersiz yatırım bakiyesi">
                            ❌ <?php echo __('buy_now'); ?>
                        </button>
                        <div style="text-align: center; margin-top: 8px;">
                            <small style="color: #e74c3c; font-weight: 500;">
                                Yetersiz yatırım bakiyesi!
                            </small>
                            <br>
                            <a href="invest.php" style="color: #3498db; text-decoration: underline; font-size: 0.8rem;">
                                Yatırım yaparak devam edin
                            </a>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <button class="btn btn-secondary btn-block" disabled>
                        <?php echo __('out_of_stock'); ?>
                    </button>
                <?php endif; ?>
                <a href="gpu_detail.php?id=<?php echo $gpu['id']; ?>" class="btn btn-secondary btn-block">
                    <?php echo __('view_details'); ?>
                </a>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Satın Alma Modal -->
<div id="purchaseModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><?php echo __('purchase_confirmation'); ?></h2>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form id="purchaseForm" method="POST" action="includes/process_purchase.php">
                <input type="hidden" name="gpu_id" id="gpu_id">
                <input type="hidden" name="payment_method" value="investment_balance">
                
                <div class="selected-product">
                    <h4><?php echo __('selected_product'); ?>:</h4>
                    <div id="selectedGpuInfo" class="product-info">
                        <!-- JavaScript ile doldurulacak -->
                    </div>
                </div>
                
                <!-- Yatırım Bakiyesi Bilgisi -->
                <div class="balance-info" style="background: #f8f9fa; padding: 1rem; border-radius: 8px; margin: 1rem 0; border-left: 4px solid #3498db;">
				<div style="display: flex; justify-content: space-between; align-items: center;">
				<span style="font-weight: 600;"><?php echo __('payment_method'); ?>:</span>
				<span style="font-weight: bold; color: #3498db;"><?php echo __('investment_balance_payment'); ?></span>
				</div>
				<div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px;">
				<span style="font-weight: 600;"><?php echo __('current_investment_balance'); ?>:</span>
				<span style="font-weight: bold; color: #3498db;"><?php echo formatPrice($user_investment_balance); ?></span>
				</div>
				<small style="color: #666; display: block; margin-top: 5px;">
				💡 <?php echo __('investment_balance_info'); ?>
				</small>
				</div>
                
                <div class="purchase-summary-modal">
                    <div class="summary-row">
                        <span><?php echo __('product_price'); ?>:</span>
                        <span id="modalPrice">0 <?php echo __('currency'); ?></span>
                    </div>
                    <div class="summary-row">
                        <span><?php echo __('monthly_income'); ?>:</span>
                        <span class="earnings" id="modalIncome">0 <?php echo __('currency'); ?></span>
                    </div>
                    <div class="summary-row total">
                        <span><?php echo __('total_investment'); ?>:</span>
                        <span class="total-price" id="modalTotal">0 <?php echo __('currency'); ?></span>
                    </div>
                    
                    <!-- Bakiye Kontrolü -->
                    <div class="summary-row" style="border-top: 2px solid #e74c3c; padding-top: 12px;">
    <span><?php echo __('investment_balance'); ?>:</span>
    <span style="font-weight: bold; color: #3498db;"><?php echo formatPrice($user_investment_balance); ?></span>
</div>
<div class="summary-row" id="balanceCheckRow" style="display: none;">
    <span><?php echo __('remaining_balance'); ?>:</span>
    <span id="remainingBalance" style="font-weight: bold; color: #27ae60;">0 <?php echo __('currency'); ?></span>
</div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        ✅ <?php echo __('confirm_purchase'); ?>
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">❌ <?php echo __('cancel'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Başarı Pop-up -->
<div id="successPopup" class="popup" style="display: none;">
    <div class="popup-content">
        <div class="popup-header success">
            <h3>✅ <?php echo __('success'); ?>!</h3>
            <span class="popup-close" onclick="closeSuccessPopup()">&times;</span>
        </div>
        <div class="popup-body">
            <div class="success-icon">🎉</div>
            <h4><?php echo __('purchase_request_received'); ?>!</h4>
            <p><strong><?php echo __('transaction_status'); ?>:</strong> <?php echo __('waiting_approval'); ?></p>
            <p><?php echo __('approval_process_description'); ?></p>
            <div class="success-actions">
                <p><?php echo __('processing_time_info'); ?></p>
            </div>
        </div>
        <div class="popup-footer">
            <button onclick="closeSuccessPopup()" class="btn btn-primary"><?php echo __('ok'); ?></button>
            <button onclick="goToMyGpus()" class="btn btn-secondary"><?php echo __('view_my_gpus'); ?></button>
        </div>
    </div>
</div>

<script>
const gpuData = <?php echo json_encode($gpus); ?>;
const userInvestmentBalance = <?php echo $user_investment_balance; ?>;

let currentGpuId = null;
let currentGpuPrice = 0;
let isSubmitting = false;

function openPurchaseModal(gpuId) {
    console.log('Opening purchase modal for GPU:', gpuId);
    currentGpuId = gpuId;
    
    const gpu = gpuData.find(g => g.id == gpuId);
    if (!gpu) {
        alert('GPU bulunamadı!');
        return;
    }

    currentGpuPrice = parseFloat(gpu.price);
    
    // Yatırım bakiyesi kontrolü
    if (userInvestmentBalance < currentGpuPrice) {
        alert('Yetersiz yatırım bakiyesi! Lütfen yatırım yapınız.');
        return;
    }

    document.getElementById('gpu_id').value = gpu.id;
    document.getElementById('selectedGpuInfo').innerHTML = '<strong>' + gpu.name + '</strong><div class="product-price">' + formatCurrency(gpu.price) + '</div>';
    document.getElementById('modalPrice').textContent = formatCurrency(gpu.price);
    document.getElementById('modalIncome').textContent = formatCurrency(gpu.monthly_income);
    document.getElementById('modalTotal').textContent = formatCurrency(gpu.price);
    
    // Kalan bakiyeyi hesapla ve göster
    const remainingBalance = userInvestmentBalance - currentGpuPrice;
    document.getElementById('remainingBalance').textContent = formatCurrency(remainingBalance);
    document.getElementById('balanceCheckRow').style.display = 'flex';

    isSubmitting = false;
    document.getElementById('purchaseModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('purchaseModal').style.display = 'none';
    currentGpuId = null;
    currentGpuPrice = 0;
    isSubmitting = false;
    document.getElementById('balanceCheckRow').style.display = 'none';
}

function openSuccessPopup() {
    document.getElementById('successPopup').style.display = 'flex';
}

function closeSuccessPopup() {
    document.getElementById('successPopup').style.display = 'none';
}

function goToMyGpus() {
    window.location.href = 'my_gpus.php';
}

document.getElementById('purchaseForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    if (isSubmitting) {
        return;
    }
    
    // Yatırım bakiyesi kontrolü
    if (userInvestmentBalance < currentGpuPrice) {
        alert('Yetersiz yatırım bakiyesi! Lütfen yatırım yapınız.');
        return;
    }
    
    const formData = new FormData(this);
    const submitBtn = document.getElementById('submitBtn');

    isSubmitting = true;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '⏳ İşleniyor...';
    
    try {
        const response = await fetch('includes/process_purchase.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            closeModal();
            openSuccessPopup();
        } else {
            alert('Hata: ' + data.message);
        }
    } catch (error) {
        console.error('Fetch error:', error);
        alert('İşlem sırasında hata oluştu!');
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '✅ Satın Alımı Onayla';
        isSubmitting = false;
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('purchaseModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    }
    
    const successPopup = document.getElementById('successPopup');
    if (successPopup) {
        successPopup.addEventListener('click', function(e) {
            if (e.target === this) {
                closeSuccessPopup();
            }
        });
    }
});

function formatCurrency(amount) {
    return '$' + number_format(amount, 2, '.', ',');
}

function number_format(number, decimals, dec_point, thousands_sep) {
    number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
    var n = !isFinite(+number) ? 0 : +number,
        prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
        sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
        dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
        s = '',
        toFixedFix = function (n, prec) {
            var k = Math.pow(10, prec);
            return '' + Math.round(n * k) / k;
        };
    s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
    if (s[0].length > 3) {
        s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
    }
    if ((s[1] || '').length < prec) {
        s[1] = s[1] || '';
        s[1] += new Array(prec - s[1].length + 1).join('0');
    }
    return s.join(dec);
}
</script>

<style>
.popup {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 2000;
    align-items: center;
    justify-content: center;
}

.popup-content {
    background: white;
    border-radius: 12px;
    width: 90%;
    max-width: 450px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    overflow: hidden;
}

.popup-header {
    padding: 1.5rem;
    text-align: center;
    color: white;
    position: relative;
}

.popup-header.success {
    background: #27ae60;
}

.popup-header h3 {
    margin: 0;
    font-size: 1.5rem;
}

.popup-close {
    position: absolute;
    top: 15px;
    right: 20px;
    font-size: 24px;
    cursor: pointer;
    opacity: 0.8;
}

.popup-body {
    padding: 2rem;
    text-align: center;
}

.popup-body h4 {
    margin: 0 0 1rem 0;
    color: #333;
    font-size: 1.2rem;
}

.popup-body p {
    margin: 0.5rem 0;
    color: #666;
    line-height: 1.5;
}

.success-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.success-actions {
    margin-top: 1.5rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.success-actions p {
    margin: 0;
    font-size: 0.9rem;
    color: #666;
}

.popup-footer {
    padding: 1rem 1.5rem;
    text-align: center;
    border-top: 1px solid #eee;
    display: flex;
    gap: 1rem;
    justify-content: center;
}

.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 20px;
    border-radius: 10px;
    width: 90%;
    max-width: 600px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.gpu-grid.detailed {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 2rem;
    margin-top: 2rem;
}

.gpu-card.detailed {
    background: var(--card-bg);
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
    position: relative;
}

.gpu-card.detailed:hover {
    transform: translateY(-5px);
}

.gpu-image-container {
    position: relative;
    margin-bottom: 1rem;
}

.gpu-image {
    width: 100%;
    height: 200px;
    object-fit: cover;
    border-radius: 8px;
}

.stock-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background: #e74c3c;
    color: white;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.gpu-specs {
    margin: 1rem 0;
}

.spec-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid var(--border-color);
}

.spec-item:last-child {
    border-bottom: none;
}

.spec-label {
    color: var(--text-color);
    opacity: 0.8;
}

.spec-value {
    font-weight: 600;
    color: var(--text-color);
}

.gpu-actions {
    margin-top: 1.5rem;
}

.purchase-summary-modal {
    background: var(--bg-color);
    padding: 1rem;
    border-radius: 8px;
    margin: 1rem 0;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid var(--border-color);
}

.summary-row:last-child {
    border-bottom: none;
}

.summary-row.total {
    font-weight: bold;
    font-size: 1.1rem;
    border-top: 2px solid var(--primary-color);
    margin-top: 8px;
    padding-top: 12px;
}

.earnings {
    color: var(--success-color);
}

.total-price {
    color: var(--primary-color);
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    margin-top: 1.5rem;
}

.btn-block {
    width: 100%;
}

.alert-warning {
    background: #f39c12;
    color: white;
    padding: 1rem;
    border-radius: 8px;
    margin: 1rem 0;
}

.alert-warning a {
    color: white;
    text-decoration: underline;
    font-weight: bold;
}

/* Responsive tasarım */
@media (max-width: 768px) {
    .balance-grid {
        grid-template-columns: 1fr;
    }
    
    .gpu-grid.detailed {
        grid-template-columns: 1fr;
    }
    
    .modal-content {
        width: 95%;
        margin: 10% auto;
        padding: 15px;
    }
}
</style>

<?php include 'includes/footer.php'; ?>