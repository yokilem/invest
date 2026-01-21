<?php
require_once 'config.php';
require_once 'functions.php';

// Hata raporlama
error_reporting(E_ALL);
ini_set('display_errors', 1);

// JSON header
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor!']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Debug için
error_log("=== PURCHASE PROCESS STARTED ===");
error_log("User ID: " . $user_id);

// POST kontrolü
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Invalid request method");
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek metodu!']);
    exit;
}

if (!isset($_POST['gpu_id'])) {
    error_log("Missing required fields");
    echo json_encode(['success' => false, 'message' => 'Eksik bilgi!']);
    exit;
}

$gpu_id = intval($_POST['gpu_id']);

error_log("GPU ID: " . $gpu_id);

// YATIRIM bakiyesini kontrol et
$user_investment_balance = getUserInvestmentBalance($user_id);

// GPU bilgilerini kontrol et
$stmt = $pdo->prepare("SELECT * FROM gpus WHERE id = ? AND is_active = 1 AND stock > 0");
$stmt->execute([$gpu_id]);
$gpu = $stmt->fetch();

if (!$gpu) {
    error_log("GPU not found or out of stock");
    echo json_encode(['success' => false, 'message' => 'GPU bulunamadı veya stokta yok!']);
    exit;
}

error_log("GPU found: " . $gpu['name'] . ", Stock: " . $gpu['stock']);
error_log("User Investment Balance: " . $user_investment_balance);

// YATIRIM bakiyesi kontrolü
if ($user_investment_balance < $gpu['price']) {
    error_log("Insufficient investment balance");
    echo json_encode(['success' => false, 'message' => 'Yetersiz yatırım bakiyesi! Lütfen yatırım yaparak devam ediniz.']);
    exit;
}

// ÇİFT İŞLEM KONTROLÜ - Aynı kullanıcı için aynı GPU'yu 10 saniye içinde kontrol et
$recent_check = $pdo->prepare("
    SELECT id FROM purchases 
    WHERE user_id = ? AND gpu_id = ? AND purchase_date > DATE_SUB(NOW(), INTERVAL 10 SECOND)
    LIMIT 1
");
$recent_check->execute([$user_id, $gpu_id]);
if ($recent_check->rowCount() > 0) {
    error_log("Duplicate purchase attempt detected");
    echo json_encode(['success' => false, 'message' => 'Bu ürün için zaten bir satın alma işleminiz bulunuyor. Lütfen bekleyin.']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Stok kontrolü ve güncelleme - ÖNCE stok düşür
    $stmt = $pdo->prepare("UPDATE gpus SET stock = stock - 1 WHERE id = ? AND stock > 0");
    $stmt->execute([$gpu_id]);
    
    $affected_rows = $stmt->rowCount();
    error_log("Stock update affected rows: " . $affected_rows);
    
    if ($affected_rows === 0) {
        throw new Exception('Stok yetersiz veya GPU bulunamadı!');
    }
    
    // Yeni stok değerini kontrol et
    $stmt = $pdo->prepare("SELECT stock FROM gpus WHERE id = ?");
    $stmt->execute([$gpu_id]);
    $new_stock = $stmt->fetch()['stock'];
    error_log("New stock: " . $new_stock);
    
    // YATIRIM bakiyesinden düş
    $stmt = $pdo->prepare("UPDATE users SET investment_balance = investment_balance - ? WHERE id = ?");
    $stmt->execute([$gpu['price'], $user_id]);
    
    // Yeni yatırım bakiyesini kontrol et
    $new_balance = getUserInvestmentBalance($user_id);
    error_log("New investment balance after purchase: " . $new_balance);
    
    // Satın alma kaydı oluştur (payment_method_id NULL olacak çünkü yatırım bakiyesi kullanılıyor)
    $stmt = $pdo->prepare("
        INSERT INTO purchases (user_id, gpu_id, amount, payment_method_id, status) 
        VALUES (?, ?, ?, NULL, 'approved')
    ");
    
    $success = $stmt->execute([
        $user_id, 
        $gpu_id, 
        $gpu['price']
    ]);
    
    if (!$success) {
        throw new Exception('Satın alma kaydı oluşturulamadı!');
    }
    
    $purchase_id = $pdo->lastInsertId();
    error_log("Purchase record created. Purchase ID: " . $purchase_id);
    
    // GPU'yu kullanıcıya ekle ve hemen aktif et
    $stmt = $pdo->prepare("
        INSERT INTO user_gpus (user_id, gpu_id, purchase_id, purchase_date, activation_time) 
        VALUES (?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([$user_id, $gpu_id, $purchase_id]);
    
    error_log("GPU added to user account and activated immediately");
    
    $pdo->commit();
    
    error_log("=== PURCHASE PROCESS COMPLETED SUCCESSFULLY ===");
    
    echo json_encode([
        'success' => true, 
        'message' => 'GPU başarıyla satın alındı! 24 saat sonra kazançlarınız başlayacak.',
        'purchase_id' => $purchase_id
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Transaction failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'İşlem sırasında hata oluştu: ' . $e->getMessage()]);
}
?>