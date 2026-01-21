<?php
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function getCurrentTheme() {
    return isset($_SESSION['theme']) ? $_SESSION['theme'] : 'light';
}

function formatPrice($price) {
    return '$' . number_format($price, 2, '.', ',');
}

function calculateDailyEarning($monthlyIncome) {
    return $monthlyIncome / 30;
}

function getPaymentMethods() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM payment_methods WHERE is_active = 1");
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}




// Tek bir GPU'nun durumunu kontrol et
function checkSingleGPUStatus($gpu_id, $user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                ug.activation_time,
                CASE 
                    WHEN ug.activation_time IS NULL THEN 'waiting_activation'
                    WHEN ug.activation_time > DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 'counting_down'
                    ELSE 'earning'
                END as status,
                TIMESTAMPDIFF(HOUR, ug.activation_time, NOW()) as hours_passed
            FROM user_gpus ug 
            WHERE ug.user_id = ? AND ug.gpu_id = ?
        ");
        $stmt->execute([$user_id, $gpu_id]);
        $result = $stmt->fetch();
        
        return $result ? $result['status'] : 'waiting_activation';
        
    } catch (Exception $e) {
        error_log("Single GPU status error: " . $e->getMessage());
        return 'waiting_activation';
    }
}



function getSetting($key, $default = '') {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['setting_value'] : $default;
    } catch (Exception $e) {
        error_log("getSetting error: " . $e->getMessage());
        return $default;
    }
}

function getGPUs() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM gpus WHERE is_active = 1 ORDER BY price ASC");
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

// Kullanıcının YATIRIM bakiyesini getir (sadece GPU satın alma için)
function getUserInvestmentBalance($user_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT investment_balance FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        return $user ? $user['investment_balance'] : 0;
    } catch (Exception $e) {
        return 0;
    }
}

// Kullanıcının KAZANÇ bakiyesini getir (sadece para çekme için)
function getUserEarningsBalance($user_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT earnings_balance FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        return $user ? $user['earnings_balance'] : 0;
    } catch (Exception $e) {
        return 0;
    }
}

// TOPLAM bakiye (sadece bilgi amaçlı)
function getTotalUserBalance($user_id) {
    $investment = getUserInvestmentBalance($user_id);
    $earnings = getUserEarningsBalance($user_id);
    return $investment + $earnings;
}

function uploadFile($file, $type = 'screenshots') {
    $target_dir = __DIR__ . '/../uploads/' . $type . '/';
    
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $file_name = uniqid() . '_' . time() . '.' . $file_extension;
    $target_file = $target_dir . $file_name;
    
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($file_extension, $allowed_types)) {
        return ['success' => false, 'error' => 'Sadece JPG, JPEG, PNG ve GIF dosyaları yükleyebilirsiniz.'];
    }
    
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'error' => 'Dosya boyutu 5MB\'dan küçük olmalıdır.'];
    }
    
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        $web_path = 'uploads/' . $type . '/' . $file_name;
        return ['success' => true, 'file_path' => $web_path];
    }
    
    return ['success' => false, 'error' => 'Dosya yüklenirken hata oluştu.'];
}

function getUserGPUs($user_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT ug.*, g.name, g.monthly_income, g.commission_rate, g.image_path, g.price
            FROM user_gpus ug 
            JOIN gpus g ON ug.gpu_id = g.id 
            WHERE ug.user_id = ?
            ORDER BY ug.purchase_date DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

function calculateReturnRate($price, $monthly_income) {
    if ($price > 0) {
        return ($monthly_income / $price) * 100;
    }
    return 0;
}

function calculateYearlyIncome($monthly_income) {
    return $monthly_income * 12;
}

function getGPUById($gpu_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM gpus WHERE id = ?");
        $stmt->execute([$gpu_id]);
        return $stmt->fetch();
    } catch (Exception $e) {
        return null;
    }
}

// Bugünkü kazancı getir (24 saat sistemine göre)
function getTodayEarnings($user_id) {
    global $pdo;
    $today = date('Y-m-d');
    
    try {
        $stmt = $pdo->prepare("
            SELECT SUM(amount) as total_earnings 
            FROM daily_earnings 
            WHERE user_id = ? AND earning_date = ?
        ");
        $stmt->execute([$user_id, $today]);
        $result = $stmt->fetch();
        
        return $result['total_earnings'] ?: 0;
        
    } catch (Exception $e) {
        return 0;
    }
}

// Toplam günlük kazancı hesapla (aktif GPU'lardan)
function getTotalDailyEarnings($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT SUM(g.monthly_income) as total_monthly_income
            FROM user_gpus ug 
            JOIN gpus g ON ug.gpu_id = g.id 
            WHERE ug.user_id = ? 
            AND ug.activation_time IS NOT NULL
            AND ug.activation_time <= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        
        $total_monthly = $result['total_monthly_income'] ?: 0;
        return $total_monthly / 30; // Günlük kazanca çevir
        
    } catch (Exception $e) {
        return 0;
    }
}

// Toplam kazancı getir
function getTotalEarnings($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT SUM(amount) as total_earnings 
            FROM daily_earnings 
            WHERE user_id = ?
        ");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        
        return $result['total_earnings'] ?: 0;
        
    } catch (Exception $e) {
        return 0;
    }
}

// 24 saat dolmuş GPU'lar için kazanç hesapla (KAZANÇ bakiyesine ekle)
function calculate24HourEarnings($user_id) {
    global $pdo;
    
    try {
        $current_time = date('Y-m-d H:i:s');
        $total_earnings = 0;
        
        // 24 saat dolmuş ve henüz bugün kazanç eklenmemiş GPU'ları getir
        $stmt = $pdo->prepare("
            SELECT 
                ug.*, 
                g.monthly_income, 
                g.name,
                TIMESTAMPDIFF(HOUR, ug.activation_time, NOW()) as hours_passed
            FROM user_gpus ug 
            JOIN gpus g ON ug.gpu_id = g.id 
            WHERE ug.user_id = ? 
            AND ug.activation_time IS NOT NULL
            AND ug.activation_time <= DATE_SUB(?, INTERVAL 24 HOUR)
            AND NOT EXISTS (
                SELECT 1 FROM daily_earnings de 
                WHERE de.user_id = ug.user_id 
                AND de.gpu_id = ug.gpu_id 
                AND DATE(de.earning_date) = CURDATE()
            )
        ");
        $stmt->execute([$user_id, $current_time]);
        $eligible_gpus = $stmt->fetchAll();
        
        foreach ($eligible_gpus as $gpu) {
            $daily_income = calculateDailyEarning($gpu['monthly_income']);
            $total_earnings += $daily_income;
            
            // Günlük kazancı kaydet
            $earning_stmt = $pdo->prepare("
                INSERT INTO daily_earnings (user_id, gpu_id, amount, earning_date, is_paid) 
                VALUES (?, ?, ?, CURDATE(), 0)
            ");
            $earning_stmt->execute([$user_id, $gpu['gpu_id'], $daily_income]);
        }
        
        // Toplam kazancı KAZANÇ bakiyesine ekle
        if ($total_earnings > 0) {
            $stmt = $pdo->prepare("UPDATE users SET earnings_balance = earnings_balance + ? WHERE id = ?");
            $stmt->execute([$total_earnings, $user_id]);
        }
        
        return $total_earnings;
        
    } catch (Exception $e) {
        error_log("24-hour earnings error: " . $e->getMessage());
        return 0;
    }
}

// GPU aktifleştirme zamanını kaydet
function activateGPU($purchase_id, $gpu_id, $user_id) {
    global $pdo;
    
    try {
        $activation_time = date('Y-m-d H:i:s');
        
        $stmt = $pdo->prepare("
            UPDATE user_gpus 
            SET activation_time = ? 
            WHERE purchase_id = ? AND gpu_id = ? AND user_id = ?
        ");
        $stmt->execute([$activation_time, $purchase_id, $gpu_id, $user_id]);
        
        return $activation_time;
        
    } catch (Exception $e) {
        error_log("GPU activation error: " . $e->getMessage());
        return false;
    }
}

// Kullanıcının GPU aktivasyon durumunu kontrol et - TEK TANIM
function checkGPUActivationStatus($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                ug.*,
                g.name,
                g.monthly_income,
                ug.activation_time,
                CASE 
                    WHEN ug.activation_time IS NULL THEN 'waiting_activation'
                    WHEN ug.activation_time > DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 'counting_down'
                    ELSE 'earning'
                END as status,
                TIMESTAMPDIFF(HOUR, ug.activation_time, NOW()) as hours_passed
            FROM user_gpus ug 
            JOIN gpus g ON ug.gpu_id = g.id 
            WHERE ug.user_id = ?
            ORDER BY ug.purchase_date DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log("GPU activation status error: " . $e->getMessage());
        return [];
    }
}

// GPU satın alma onaylandığında (YATIRIM bakiyesinden düş)
function approveGPUPurchase($purchase_id) {
    global $pdo;
    
    try {
        // Satın alma bilgilerini getir
        $stmt = $pdo->prepare("
            SELECT p.user_id, p.gpu_id, p.amount 
            FROM purchases p 
            WHERE p.id = ? AND p.status = 'pending'
        ");
        $stmt->execute([$purchase_id]);
        $purchase = $stmt->fetch();
        
        if ($purchase) {
            // Yatırım bakiyesini kontrol et
            $user_balance = getUserInvestmentBalance($purchase['user_id']);
            if ($user_balance < $purchase['amount']) {
                return ['success' => false, 'message' => 'Yetersiz yatırım bakiyesi!'];
            }
            
            // YATIRIM bakiyesinden düş
            $stmt = $pdo->prepare("UPDATE users SET investment_balance = investment_balance - ? WHERE id = ?");
            $stmt->execute([$purchase['amount'], $purchase['user_id']]);
            
            // GPU'yu aktif et
            $activation_time = activateGPU($purchase_id, $purchase['gpu_id'], $purchase['user_id']);
            
            if ($activation_time) {
                // Satın alma durumunu güncelle
                $stmt = $pdo->prepare("UPDATE purchases SET status = 'approved' WHERE id = ?");
                $stmt->execute([$purchase_id]);
                
                return [
                    'success' => true,
                    'activation_time' => $activation_time,
                    'message' => 'GPU activated successfully. Earnings will start after 24 hours.'
                ];
            }
        }
        
        return ['success' => false, 'message' => 'Purchase not found or already approved'];
        
    } catch (Exception $e) {
        error_log("Approve GPU purchase error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

// Yatırım onaylandığında YATIRIM bakiyesine ekle
function approveInvestment($investment_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT user_id, amount 
            FROM investments 
            WHERE id = ? AND status = 'pending'
        ");
        $stmt->execute([$investment_id]);
        $investment = $stmt->fetch();
        
        if ($investment) {
            // YATIRIM bakiyesine ekle
            $stmt = $pdo->prepare("UPDATE users SET investment_balance = investment_balance + ? WHERE id = ?");
            $stmt->execute([$investment['amount'], $investment['user_id']]);
            
            // Yatırım durumunu güncelle
            $stmt = $pdo->prepare("UPDATE investments SET status = 'approved' WHERE id = ?");
            $stmt->execute([$investment_id]);
            
            return ['success' => true, 'message' => 'Yatırım onaylandı ve yatırım bakiyesine eklendi.'];
        }
        
        return ['success' => false, 'message' => 'Yatırım bulunamadı veya zaten onaylanmış.'];
        
    } catch (Exception $e) {
        error_log("Approve investment error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

// Kullanıcının toplam yatırım miktarını getir
function getTotalInvestmentAmount($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT SUM(amount) as total_investment 
            FROM investments 
            WHERE user_id = ? AND status = 'approved'
        ");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        
        return $result['total_investment'] ?: 0;
        
    } catch (Exception $e) {
        return 0;
    }
}

// Kullanıcının toplam satın alma miktarını getir
function getTotalPurchaseAmount($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT SUM(amount) as total_purchase 
            FROM purchases 
            WHERE user_id = ? AND status = 'approved'
        ");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        
        return $result['total_purchase'] ?: 0;
        
    } catch (Exception $e) {
        return 0;
    }
}

// Kullanıcının toplam çekim miktarını getir
function getTotalWithdrawalAmount($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT SUM(amount) as total_withdrawal 
            FROM withdrawals 
            WHERE user_id = ? AND status = 'approved'
        ");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        
        return $result['total_withdrawal'] ?: 0;
        
    } catch (Exception $e) {
        return 0;
    }
}

// Kullanıcının aktif GPU sayısını getir
function getActiveGPUCount($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as active_gpus
            FROM user_gpus ug 
            WHERE ug.user_id = ? 
            AND ug.activation_time IS NOT NULL
            AND ug.activation_time <= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        
        return $result['active_gpus'] ?: 0;
        
    } catch (Exception $e) {
        return 0;
    }
}

// Kullanıcının bekleyen GPU sayısını getir
function getPendingGPUCount($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as pending_gpus
            FROM user_gpus ug 
            WHERE ug.user_id = ? 
            AND (ug.activation_time IS NULL OR ug.activation_time > DATE_SUB(NOW(), INTERVAL 24 HOUR))
        ");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        
        return $result['pending_gpus'] ?: 0;
        
    } catch (Exception $e) {
        return 0;
    }
}

// Ödeme yöntemi bilgilerini getir
function getPaymentMethodById($method_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM payment_methods WHERE id = ?");
        $stmt->execute([$method_id]);
        return $stmt->fetch();
    } catch (Exception $e) {
        return null;
    }
}

// Kullanıcı bilgilerini getir
function getUserById($user_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    } catch (Exception $e) {
        return null;
    }
}

// Tüm kullanıcıları getir (admin için)
function getAllUsers() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

// Tüm yatırımları getir (admin için)
function getAllInvestments() {
    global $pdo;
    try {
        $stmt = $pdo->query("
            SELECT i.*, u.username, u.email, pm.coin_name 
            FROM investments i 
            JOIN users u ON i.user_id = u.id 
            LEFT JOIN payment_methods pm ON i.payment_method_id = pm.id 
            ORDER BY i.investment_date DESC
        ");
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

// Tüm satın alımları getir (admin için)
function getAllPurchases() {
    global $pdo;
    try {
        $stmt = $pdo->query("
            SELECT p.*, u.username, u.email, g.name as gpu_name, pm.coin_name 
            FROM purchases p 
            JOIN users u ON p.user_id = u.id 
            JOIN gpus g ON p.gpu_id = g.id 
            LEFT JOIN payment_methods pm ON p.payment_method_id = pm.id 
            ORDER BY p.purchase_date DESC
        ");
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

// Tüm çekimleri getir (admin için)
function getAllWithdrawals() {
    global $pdo;
    try {
        $stmt = $pdo->query("
            SELECT w.*, u.username, u.email 
            FROM withdrawals w 
            JOIN users u ON w.user_id = u.id 
            ORDER BY w.created_at DESC
        ");
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

// Site istatistiklerini getir (admin için)
function getSiteStatistics() {
    global $pdo;
    
    $stats = [];
    
    try {
        // Toplam kullanıcı sayısı
        $stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users WHERE role = 'user'");
        $stats['total_users'] = $stmt->fetch()['total_users'];
        
        // Toplam yatırım miktarı
        $stmt = $pdo->query("SELECT SUM(amount) as total_investment FROM investments WHERE status = 'approved'");
        $stats['total_investment'] = $stmt->fetch()['total_investment'] ?: 0;
        
        // Toplam satın alma miktarı
        $stmt = $pdo->query("SELECT SUM(amount) as total_purchase FROM purchases WHERE status = 'approved'");
        $stats['total_purchase'] = $stmt->fetch()['total_purchase'] ?: 0;
        
        // Toplam çekim miktarı
        $stmt = $pdo->query("SELECT SUM(amount) as total_withdrawal FROM withdrawals WHERE status = 'approved'");
        $stats['total_withdrawal'] = $stmt->fetch()['total_withdrawal'] ?: 0;
        
        // Bekleyen işlemler
        $stmt = $pdo->query("
            SELECT COUNT(*) as pending_transactions FROM (
                SELECT id FROM investments WHERE status = 'pending'
                UNION ALL
                SELECT id FROM purchases WHERE status = 'pending'
                UNION ALL
                SELECT id FROM withdrawals WHERE status = 'pending'
            ) as pending
        ");
        $stats['pending_transactions'] = $stmt->fetch()['pending_transactions'];
        
        // Aktif GPU sayısı
        $stmt = $pdo->query("SELECT COUNT(*) as active_gpus FROM user_gpus WHERE activation_time IS NOT NULL");
        $stats['active_gpus'] = $stmt->fetch()['active_gpus'];
        
        return $stats;
        
    } catch (Exception $e) {
        error_log("Site statistics error: " . $e->getMessage());
        return [
            'total_users' => 0,
            'total_investment' => 0,
            'total_purchase' => 0,
            'total_withdrawal' => 0,
            'pending_transactions' => 0,
            'active_gpus' => 0
        ];
    }
}
?>