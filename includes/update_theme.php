<?php
require_once 'config.php';
require_once 'functions.php';

// Hata raporlama
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session başlat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isLoggedIn()) {
    echo "ERROR: Not logged in";
    exit;
}

if ($_POST && isset($_POST['theme'])) {
    $theme = $_POST['theme'];
    $user_id = $_SESSION['user_id'];
    
    if (in_array($theme, ['light', 'dark'])) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET theme = ? WHERE id = ?");
            $success = $stmt->execute([$theme, $user_id]);
            
            if ($success) {
                $_SESSION['theme'] = $theme;
                echo "SUCCESS: Theme updated to " . $theme;
            } else {
                echo "ERROR: Database update failed";
            }
        } catch (Exception $e) {
            echo "ERROR: " . $e->getMessage();
        }
    } else {
        echo "ERROR: Invalid theme";
    }
} else {
    echo "ERROR: No theme data";
}
?>