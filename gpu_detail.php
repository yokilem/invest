<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('buy_gpu.php');
}

$gpu_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];
$page_title = __("gpu_details");

// Başarı/error mesajlarını kontrol et
$success = isset($_SESSION['success']) ? $_SESSION['success'] : '';
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['success']);
unset($_SESSION['error']);

// GPU bilgilerini getir
$stmt = $pdo->prepare("SELECT * FROM gpus WHERE id = ? AND is_active = 1");
$stmt->execute([$gpu_id]);
$gpu = $stmt->fetch();

if (!$gpu) {
    redirect('buy_gpu.php');
}

// Kullanıcı bakiyelerini getir
$user_investment_balance = getUserInvestmentBalance($user_id);
$user_earnings_balance = getUserEarningsBalance($user_id);

$dailyEarning = calculateDailyEarning($gpu['monthly_income']);
$can_afford = $user_investment_balance >= $gpu['price'];

// Benzer GPU'ları getir
$similar_gpus = $pdo->prepare("
    SELECT * FROM gpus 
    WHERE id != ? AND is_active = 1 AND stock > 0 
    ORDER BY price ASC 
    LIMIT 3
");
$similar_gpus->execute([$gpu_id]);
$similar_gpus = $similar_gpus->fetchAll();
?>
<?php include 'includes/header.php'; ?>

<div class="page-header">
    <h1><?php echo $gpu['name']; ?></h1>
    <p><?php echo __('gpu_details_description'); ?></p>
    
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

<div class="content-row">
    <div class="content-col">
        <div class="content-card">
            <div class="gpu-detail-header">
                <div class="gpu-image-large">
                    <img src="<?php echo $gpu['image_path'] ?: 'https://via.placeholder.com/400x300/3498db/ffffff?text=GPU+Image'; ?>" 
                         alt="<?php echo $gpu['name']; ?>" 
                         class="gpu-image">
                </div>
                <div class="gpu-quick-info">
                    <h2><?php echo $gpu['name']; ?></h2>
                    <div class="price-tag"><?php echo formatPrice($gpu['price']); ?></div>
                    
                    <div class="stock-info">
                        <?php if ($gpu['stock'] > 0): ?>
                            <span class="stock-available">✅ <?php echo __('stock_available'); ?>: <?php echo $gpu['stock']; ?> <?php echo __('quantity'); ?></span>
                        <?php else: ?>
                            <span class="stock-out">❌ <?php echo __('out_of_stock'); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Yatırım Bakiyesi Kontrollü Satın Alma Butonu -->
                    <?php if ($gpu['stock'] > 0): ?>
                        <?php if ($can_afford): ?>
                            <button onclick="openPurchaseModal(<?php echo $gpu['id']; ?>)" 
                                    class="btn btn-primary btn-large">
                                🛒 <?php echo __('buy_now'); ?>
                            </button>
                        <?php else: ?>
                            <button class="btn btn-secondary btn-large" disabled title="Yetersiz yatırım bakiyesi">
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
                        <button class="btn btn-secondary btn-large" disabled>
                            🔄 <?php echo __('waiting_stock'); ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- ... Diğer GPU detay içeriği aynı kalacak ... -->
            
        </div>
        
        <!-- Kazanç Hesaplama -->
        <div class="content-card">
            <h3><?php echo __('earnings_calculator'); ?></h3>
            <div class="earnings-calculator">
                <!-- ... Kazanç hesaplama içeriği aynı kalacak ... -->
            </div>
        </div>
    </div>
    
    <div class="content-col">
        <!-- Hızlı Satın Alma -->
        <div class="content-card quick-purchase">
            <h3><?php echo __('quick_purchase'); ?></h3>
            
            <div class="purchase-summary">
                <div class="summary-item">
                    <span><?php echo __('product'); ?>:</span>
                    <span><?php echo $gpu['name']; ?></span>
                </div>
                <div class="summary-item">
                    <span><?php echo __('price'); ?>:</span>
                    <span class="price"><?php echo formatPrice($gpu['price']); ?></span>
                </div>
                <div class="summary-item">
                    <span><?php echo __('monthly_income'); ?>:</span>
                    <span class="earnings">+<?php echo formatPrice($gpu['monthly_income']); ?></span>
                </div>
                
                <!-- Yatırım Bakiyesi Durumu -->
                <div class="summary-item" style="border-top: 2px solid <?php echo $can_afford ? '#27ae60' : '#e74c3c'; ?>;">
                    <span>Durum:</span>
                    <span style="color: <?php echo $can_afford ? '#27ae60' : '#e74c3c'; ?>; font-weight: bold;">
    <?php if ($can_afford): ?>
        ✅ <?php echo __('status_purchasable'); ?>
    <?php else: ?>
        ❌ <?php echo __('status_insufficient_balance'); ?>
    <?php endif; ?>
</span>
                </div>
                
                <div class="summary-item total">
                    <span><?php echo __('total_investment'); ?>:</span>
                    <span class="total-price"><?php echo formatPrice($gpu['price']); ?></span>
                </div>
            </div>
            
            <?php if ($gpu['stock'] > 0): ?>
                <?php if ($can_afford): ?>
                    <button onclick="openPurchaseModal(<?php echo $gpu['id']; ?>)" 
                            class="btn btn-primary btn-block btn-large">
                        🛒 <?php echo __('buy_now'); ?>
                    </button>
                <?php else: ?>
                    <button class="btn btn-secondary btn-block btn-large" disabled title="Yetersiz yatırım bakiyesi">
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
                
                <div class="purchase-features">
                    <div class="feature">
                        <span class="feature-icon">⚡</span>
                        <span><?php echo __('instant_activation'); ?></span>
                    </div>
                    <div class="feature">
                        <span class="feature-icon">🛡️</span>
                        <span><?php echo __('secure_payment'); ?></span>
                    </div>
                    <div class="feature">
                        <span class="feature-icon">📈</span>
                        <span><?php echo __('earnings_24_7'); ?></span>
                    </div>
                    <div class="feature">
                        <span class="feature-icon">🔧</span>
                        <span><?php echo __('technical_support'); ?></span>
                    </div>
                </div>
            <?php else: ?>
                <button class="btn btn-secondary btn-block btn-large" disabled>
                    🔄 <?php echo __('out_of_stock'); ?>
                </button>
                <p class="stock-notify">
                    <?php echo __('notify_stock_message'); ?> <a href="#" onclick="alert('<?php echo __('notification_added'); ?>')"><?php echo __('notification_list'); ?></a>.
                </p>
            <?php endif; ?>
        </div>
        
        <!-- ... Diğer içerikler (benzer ürünler, performans istatistikleri) aynı kalacak ... -->
        
    </div>
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
<div id="successPopup" class="popup">
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
// GPU verileri
const gpuData = {
    id: <?php echo $gpu['id']; ?>,
    name: "<?php echo $gpu['name']; ?>",
    price: <?php echo $gpu['price']; ?>,
    monthly_income: <?php echo $gpu['monthly_income']; ?>
};

const userInvestmentBalance = <?php echo $user_investment_balance; ?>;

let currentGpuId = null;
let currentGpuPrice = 0;
let isSubmitting = false;

function openPurchaseModal(gpuId) {
    console.log('Opening purchase modal for GPU:', gpuId);
    currentGpuId = gpuId;
    
    const gpu = gpuData;
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

// Form gönderme işlemi
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

// Modal event listener'ları
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

// Kazanç hesaplama (mevcut kod)
if (document.getElementById('gpu_count')) {
    document.getElementById('gpu_count').addEventListener('input', function() {
        const count = parseInt(this.value) || 1;
        const dailyEarning = <?php echo $dailyEarning; ?>;
        const monthlyIncome = <?php echo $gpu['monthly_income']; ?>;
        
        document.getElementById('daily_result').textContent = formatCurrency(dailyEarning * count);
        document.getElementById('monthly_result').textContent = formatCurrency(monthlyIncome * count);
        document.getElementById('yearly_result').textContent = formatCurrency(monthlyIncome * 12 * count);
        
        const roiMonths = <?php echo $gpu['price']; ?> / monthlyIncome;
        document.getElementById('roi_result').textContent = (roiMonths / count).toFixed(1) + ' ay';
    });
}
</script>

<!-- CSS Stilleri (buy_gpu.php'den alınan stiller) -->
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
    
    .modal-content {
        width: 95%;
        margin: 10% auto;
        padding: 15px;
    }
}
</style>

<?php include 'includes/footer.php'; ?>