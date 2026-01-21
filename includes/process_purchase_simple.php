<?php
require_once 'config.php';
require_once 'functions.php';

session_start();

if (!isLoggedIn()) {
    $_SESSION['error'] = 'Oturum açmanız gerekiyor!';
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_POST && isset($_POST['gpu_id'])) {
    $gpu_id = intval($_POST['gpu_id']);
    $payment_method_id = intval($_POST['payment_method']);
    
    // GPU bilgilerini kontrol et
    $stmt = $pdo->prepare("SELECT * FROM gpus WHERE id = ? AND is_active = 1 AND stock > 0");
    $stmt->execute([$gpu_id]);
    $gpu = $stmt->fetch();
    
    if (!$gpu) {
        $_SESSION['error'] = 'GPU bulunamadı veya stokta yok!';
        header('Location: ../buy_gpu.php');
        exit;
    }
    
    // Dosya yükleme
    if (!empty($_FILES['screenshot']['name'])) {
        $upload_result = uploadFile($_FILES['screenshot'], 'screenshots');
        
        if ($upload_result['success']) {
            try {
                $pdo->beginTransaction();
                
                // Satın alma kaydı oluştur
                $stmt = $pdo->prepare("
                    INSERT INTO purchases (user_id, gpu_id, amount, screenshot_path, payment_method_id) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $user_id, 
                    $gpu_id, 
                    $gpu['price'], 
                    $upload_result['file_path'], 
                    $payment_method_id
                ]);
                
                // Stok güncelleme
                $stmt = $pdo->prepare("UPDATE gpus SET stock = stock - 1 WHERE id = ?");
                $stmt->execute([$gpu_id]);
                
                $pdo->commit();
                
                $_SESSION['success'] = 'Satın alma talebiniz alındı! Onaylandıktan sonra GPU hesabınıza eklenecek.';
                header('Location: ../my_gpus.php');
                exit;
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['error'] = 'İşlem sırasında hata oluştu: ' . $e->getMessage();
                header('Location: ../gpu_detail.php?id=' . $gpu_id);
                exit;
            }
        } else {
            $_SESSION['error'] = $upload_result['error'];
            header('Location: ../gpu_detail.php?id=' . $gpu_id);
            exit;
        }
    } else {
        $_SESSION['error'] = 'Ödeme ekran görüntüsü yüklemelisiniz!';
        header('Location: ../gpu_detail.php?id=' . $gpu_id);
        exit;
    }
} else {
    $_SESSION['error'] = 'Geçersiz istek!';
    header('Location: ../buy_gpu.php');
    exit;
}
?>