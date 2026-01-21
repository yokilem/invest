<?php
// cron_trigger.php - Manuel cron tetikleyici
if (!isset($_GET['key']) || $_GET['key'] !== 'your_secret_key_123') {
    die('Unauthorized access!');
}

require_once 'includes/config.php';
require_once 'includes/functions.php';

echo "<h3>GPU Investment - Daily Earnings Calculator</h3>";
echo "<p>Starting calculation at: " . date('Y-m-d H:i:s') . "</p>";

try {
    // Tüm aktif kullanıcılar için günlük kazanç hesapla
    $users = $pdo->query("SELECT id, username FROM users WHERE role = 'user'")->fetchAll();
    $processed_users = 0;
    $total_earnings = 0;
    
    echo "<p>Found " . count($users) . " users</p>";
    echo "<ul>";

    foreach ($users as $user) {
        $earnings = calculateAndAddDailyEarnings($user['id']);
        if ($earnings > 0) {
            $processed_users++;
            $total_earnings += $earnings;
            echo "<li>✅ {$user['username']}: $" . number_format($earnings, 2) . "</li>";
        } else {
            echo "<li>❌ {$user['username']}: No earnings today</li>";
        }
    }

    echo "</ul>";
    echo "<h4>✅ COMPLETED: Processed {$processed_users} users, Total earnings: $" . number_format($total_earnings, 2) . "</h4>";
    
} catch (Exception $e) {
    echo "<h4>❌ ERROR: " . $e->getMessage() . "</h4>";
}

echo "<p>Finished at: " . date('Y-m-d H:i:s') . "</p>";
?>