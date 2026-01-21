<?php
// Natro için GPU Investment 24-Hour Earnings Cron
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/cron_errors.log');

// Natro'da session başlatma
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// NATRO YOL AYARI - Bu kısmı değiştirmeniz gerekebilir
$base_path = dirname(dirname(__FILE__));
require_once $base_path . '/includes/config.php';
require_once $base_path . '/includes/functions.php';

// Log dosyası
$log_file = dirname(__FILE__) . '/cron.log';
$log_message = "=== 24-HOUR EARNINGS CRON STARTED - " . date('Y-m-d H:i:s') . " ===\n";

try {
    // Veritabanı bağlantısını kontrol et
    $log_message .= "🔍 Checking database connection...\n";
    
    // Tüm aktif kullanıcıları getir
    $users = $pdo->query("SELECT id, username, balance FROM users WHERE role = 'user'")->fetchAll();
    $log_message .= "👥 Found " . count($users) . " users\n\n";
    
    $total_processed_users = 0;
    $total_earnings = 0;
    $total_eligible_gpus = 0;

    foreach ($users as $user) {
        $log_message .= "➡️ Processing User: {$user['username']} (Current Balance: $" . number_format($user['balance'], 2) . ")\n";
        
        // Kullanıcının GPU'larını kontrol et
        $user_gpus = $pdo->prepare("
            SELECT 
                ug.id, ug.gpu_id, ug.activation_time,
                g.name, g.monthly_income,
                TIMESTAMPDIFF(HOUR, ug.activation_time, NOW()) as hours_passed
            FROM user_gpus ug 
            JOIN gpus g ON ug.gpu_id = g.id 
            WHERE ug.user_id = ? 
            AND ug.activation_time IS NOT NULL
        ");
        $user_gpus->execute([$user['id']]);
        $gpus = $user_gpus->fetchAll();
        
        $log_message .= "   🖥️ User GPUs: " . count($gpus) . " (with activation time)\n";
        
        $user_earnings = 0;
        $user_eligible_gpus = 0;
        
        foreach ($gpus as $gpu) {
            $log_message .= "   ├── GPU: {$gpu['name']}\n";
            $log_message .= "   │   ├── Activation: " . $gpu['activation_time'] . "\n";
            $log_message .= "   │   ├── Hours Passed: " . $gpu['hours_passed'] . "h\n";
            
            // 24 saat dolmuş mu kontrol et
            if ($gpu['hours_passed'] >= 24) {
                $log_message .= "   │   ├── Status: ✅ 24+ hours passed\n";
                
                // Bugün için bu GPU'dan kazanç eklenmiş mi kontrol et
                $existing_earning = $pdo->prepare("
                    SELECT id FROM daily_earnings 
                    WHERE user_id = ? AND gpu_id = ? AND DATE(earning_date) = CURDATE()
                ");
                $existing_earning->execute([$user['id'], $gpu['gpu_id']]);
                
                if (!$existing_earning->fetch()) {
                    // Kazanç hesapla
                    $daily_income = calculateDailyEarning($gpu['monthly_income']);
                    $user_earnings += $daily_income;
                    $user_eligible_gpus++;
                    
                    $log_message .= "   │   ├── Daily Income: $" . number_format($daily_income, 2) . "\n";
                    
                    // Günlük kazancı kaydet
                    $earning_stmt = $pdo->prepare("
                        INSERT INTO daily_earnings (user_id, gpu_id, amount, earning_date, is_paid) 
                        VALUES (?, ?, ?, CURDATE(), 0)
                    ");
                    $earning_stmt->execute([$user['id'], $gpu['gpu_id'], $daily_income]);
                    
                    $log_message .= "   │   └── ✅ Earnings recorded\n";
                } else {
                    $log_message .= "   │   └── ℹ️ Already earned today\n";
                }
            } else {
                $hours_left = 24 - $gpu['hours_passed'];
                $log_message .= "   │   └── Status: ⏳ {$hours_left}h left\n";
            }
        }
        
        // Toplam kazancı bakiyeye ekle
        if ($user_earnings > 0) {
            $update_balance = $pdo->prepare("UPDATE users SET earnings_balance = earnings_balance + ? WHERE id = ?");
            $update_balance->execute([$user_earnings, $user['id']]);
            
            // Yeni bakiyeyi al
            $new_balance = $pdo->prepare("SELECT earnings_balance FROM users WHERE id = ?");
            $new_balance->execute([$user['id']]);
            $new_balance_result = $new_balance->fetch();
            
            $log_message .= "   ├── 💰 Earnings Added: $" . number_format($user_earnings, 2) . "\n";
            $log_message .= "   ├── 💳 New Balance: $" . number_format($new_balance_result['balance'], 2) . "\n";
            $log_message .= "   └── ✅ User processed successfully\n";
            
            $total_processed_users++;
            $total_earnings += $user_earnings;
            $total_eligible_gpus += $user_eligible_gpus;
        } else {
            $log_message .= "   └── ℹ️ No eligible earnings for this user\n";
        }
        
        $log_message .= "\n";
    }

    $log_message .= "📊 FINAL RESULTS:\n";
    $log_message .= "================\n";
    $log_message .= "✅ Processed Users: {$total_processed_users}\n";
    $log_message .= "🖥️ Eligible GPUs: {$total_eligible_gpus}\n";
    $log_message .= "💰 Total Earnings: $" . number_format($total_earnings, 2) . "\n";
    $log_message .= "⏰ Finished at: " . date('Y-m-d H:i:s') . "\n";
    
} catch (Exception $e) {
    $log_message .= "❌ CRITICAL ERROR: " . $e->getMessage() . "\n";
    $log_message .= "📂 File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    $log_message .= "🔍 Stack Trace: " . $e->getTraceAsString() . "\n";
}

$log_message .= "=== 24-HOUR EARNINGS CRON FINISHED ===\n\n";

// Log'u dosyaya yaz
file_put_contents($log_file, $log_message, FILE_APPEND | LOCK_EX);

// Çıktıyı göster (cron logunda görünsün)
echo $log_message;

// Email bildirimi (opsiyonel - aktif etmek isterseniz)
/*
$to = "your-email@gmail.com";
$subject = "24-Hour GPU Earnings Report - " . date('Y-m-d');
$headers = "From: cron@yourdomain.com\r\n" .
           "Content-Type: text/plain; charset=utf-8\r\n";
           
if ($total_earnings > 0) {
    mail($to, $subject, $log_message, $headers);
}
*/
?>