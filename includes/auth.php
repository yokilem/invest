<?php
require_once 'config.php';

function loginUser($username, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['theme'] = $user['theme'];
        return true;
    }
    return false;
}

function registerUser($username, $email, $password) {
    global $pdo;
    
    // Kullanıcı adı ve email kontrolü
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    
    if ($stmt->rowCount() > 0) {
        return false;
    }
    
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    return $stmt->execute([$username, $email, $hashedPassword]);
}

function updateUserTheme($user_id, $theme) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE users SET theme = ? WHERE id = ?");
    return $stmt->execute([$theme, $user_id]);
}
?>