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

if (!isset($_POST['gpu_id']) || !isset($_POST['payment_method'])) {
    error_log("Missing required fields");
    echo json_encode(['success' => false, 'message' => 'Eksik bilgi!']);
    exit;
}

$gpu_id = intval($_POST['gpu_id']);
$payment_method_id = intval($_POST['payment_method']);

error_log("GPU ID: " . $gpu_id . ", Payment Method: " . $payment_method_id);

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

// Dosya yükleme kontrolü
if (empty($_FILES['screenshot']['name'])) {
    error_log("No screenshot uploaded");
    echo json_encode(['success' => false, 'message' => 'Ödeme ekran görüntüsü yüklemelisiniz!']);
    exit;
}

// Dosya yükleme
$upload_result = uploadFile($_FILES['screenshot'], 'screenshots');

if (!$upload_result['success']) {
    error_log("File upload failed: " . $upload_result['error']);
    echo json_encode(['success' => false, 'message' => $upload_result['error']]);
    exit;
}

error_log("File uploaded successfully: " . $upload_result['file_path']);

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
    
    // Satın alma kaydı oluştur
    $stmt = $pdo->prepare("
        INSERT INTO purchases (user_id, gpu_id, amount, screenshot_path, payment_method_id, status) 
        VALUES (?, ?, ?, ?, ?, 'pending')
    ");
    
    $success = $stmt->execute([
        $user_id, 
        $gpu_id, 
        $gpu['price'], 
        $upload_result['file_path'], 
        $payment_method_id
    ]);
    
    if (!$success) {
        throw new Exception('Satın alma kaydı oluşturulamadı!');
    }
    
    $purchase_id = $pdo->lastInsertId();
    error_log("Purchase record created. Purchase ID: " . $purchase_id);
    
    $pdo->commit();
    
    error_log("=== PURCHASE PROCESS COMPLETED SUCCESSFULLY ===");
    
    echo json_encode([
        'success' => true, 
        'message' => 'Satın alma talebiniz alındı! Onaylandıktan sonra GPU\'nuz hesabınıza eklenecektir.',
        'purchase_id' => $purchase_id
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Transaction failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'İşlem sırasında hata oluştu: ' . $e->getMessage()]);
}
?>