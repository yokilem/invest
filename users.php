<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// ADMIN GİRİŞ KONTROLÜ
if (!isLoggedIn()) {
    redirect('login.php');
}

if (!isAdmin()) {
    redirect('../dashboard.php');
}

$page_title = "Kullanıcı Yönetimi";
$admin_page = true;

// Kullanıcı düzenleme
if ($_POST && isset($_POST['update_user'])) {
    $user_id = intval($_POST['user_id']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $balance = floatval($_POST['balance']);
    
    try {
        // Kullanıcı adı ve email kontrolü (başka kullanıcılarda var mı?)
        $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->execute([$username, $email, $user_id]);
        
        if ($stmt->rowCount() > 0) {
            $error = "Bu kullanıcı adı veya e-posta adresi başka bir kullanıcı tarafından kullanılıyor!";
        } else {
            // Kullanıcıyı güncelle
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, balance = ? WHERE id = ?");
            if ($stmt->execute([$username, $email, $balance, $user_id])) {
                $success = "Kullanıcı bilgileri başarıyla güncellendi!";
            } else {
                $error = "Kullanıcı güncellenirken hata oluştu!";
            }
        }
    } catch (Exception $e) {
        $error = "Hata: " . $e->getMessage();
    }
}

// Kullanıcı silme
if (isset($_GET['delete'])) {
    $user_id = intval($_GET['delete']);
    
    try {
        $pdo->beginTransaction();
        
        // İlişkili kayıtları sil
        $tables = ['user_gpus', 'investments', 'purchases', 'withdrawals'];
        foreach ($tables as $table) {
            $stmt = $pdo->prepare("DELETE FROM {$table} WHERE user_id = ?");
            $stmt->execute([$user_id]);
        }
        
        // Kullanıcıyı sil (admin hariç)
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
        $stmt->execute([$user_id]);
        
        if ($stmt->rowCount() > 0) {
            $pdo->commit();
            $success = "Kullanıcı ve tüm verileri başarıyla silindi!";
        } else {
            $pdo->rollBack();
            $error = "Admin kullanıcılar silinemez!";
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Kullanıcı silinirken hata oluştu: " . $e->getMessage();
    }
    
    // Sayfayı yenile
    header("Location: users.php");
    exit;
}

// Bakiye güncelleme
if ($_POST && isset($_POST['update_balance'])) {
    $user_id = intval($_POST['user_id']);
    $balance_change = floatval($_POST['balance_change']);
    $change_type = $_POST['change_type']; // add or subtract
    $reason = trim($_POST['reason']);
    
    try {
        // Mevcut bakiyeyi getir
        $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if ($user) {
            $current_balance = $user['balance'];
            $new_balance = $change_type == 'add' ? 
                $current_balance + $balance_change : 
                $current_balance - $balance_change;
            
            if ($new_balance >= 0) {
                // Bakiyeyi güncelle
                $stmt = $pdo->prepare("UPDATE users SET balance = ? WHERE id = ?");
                if ($stmt->execute([$new_balance, $user_id])) {
                    $success = "Kullanıcı bakiyesi başarıyla güncellendi! Yeni bakiye: " . formatPrice($new_balance);
                }
            } else {
                $error = "Bakiye negatif olamaz!";
            }
        }
    } catch (Exception $e) {
        $error = "Bakiye güncellenirken hata oluştu: " . $e->getMessage();
    }
}

// Ekran kartı silme
if (isset($_GET['delete_gpu'])) {
    $user_gpu_id = intval($_GET['delete_gpu']);
    $user_id = intval($_GET['user_id']);
    
    try {
        $stmt = $pdo->prepare("DELETE FROM user_gpus WHERE id = ? AND user_id = ?");
        if ($stmt->execute([$user_gpu_id, $user_id])) {
            $success = "Ekran kartı kullanıcıdan başarıyla silindi!";
        } else {
            $error = "Ekran kartı silinirken hata oluştu!";
        }
    } catch (Exception $e) {
        $error = "Hata: " . $e->getMessage();
    }
    
    // Sayfayı yenile
    header("Location: users.php?edit=" . $user_id);
    exit;
}

// Ekran kartı ekleme
if ($_POST && isset($_POST['add_gpu'])) {
    $user_id = intval($_POST['user_id']);
    $gpu_id = intval($_POST['gpu_id']);
    
    try {
        // GPU bilgilerini getir
        $stmt = $pdo->prepare("SELECT * FROM gpus WHERE id = ?");
        $stmt->execute([$gpu_id]);
        $gpu = $stmt->fetch();
        
        if ($gpu) {
            // Kullanıcıya GPU'yu ekle
            $stmt = $pdo->prepare("INSERT INTO user_gpus (user_id, gpu_id, purchase_date) VALUES (?, ?, NOW())");
            if ($stmt->execute([$user_id, $gpu_id])) {
                $success = "Ekran kartı kullanıcıya başarıyla eklendi!";
            } else {
                $error = "Ekran kartı eklenirken hata oluştu!";
            }
        } else {
            $error = "Ekran kartı bulunamadı!";
        }
    } catch (Exception $e) {
        $error = "Hata: " . $e->getMessage();
    }
    
    // Sayfayı yenile
    header("Location: users.php?edit=" . $user_id);
    exit;
}

// Kullanıcı listesini getir
$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();

// Seçili kullanıcı bilgilerini getir (düzenleme için)
$selected_user = null;
$user_gpus = [];
$user_transactions = [];
$available_gpus = [];

if (isset($_GET['edit'])) {
    $user_id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $selected_user = $stmt->fetch();
    
    if ($selected_user) {
        // Kullanıcının GPU'larını getir
        $stmt = $pdo->prepare("
            SELECT ug.*, g.name, g.monthly_income, g.image_path, g.price
            FROM user_gpus ug 
            JOIN gpus g ON ug.gpu_id = g.id 
            WHERE ug.user_id = ?
            ORDER BY ug.purchase_date DESC
        ");
        $stmt->execute([$user_id]);
        $user_gpus = $stmt->fetchAll();
        
        // Kullanıcının son işlemlerini getir
        $stmt = $pdo->prepare("
            (SELECT 'investment' as type, amount, status, investment_date as date 
             FROM investments WHERE user_id = ?)
            UNION
            (SELECT 'purchase' as type, amount, status, purchase_date as date 
             FROM purchases WHERE user_id = ?)
            UNION
            (SELECT 'withdrawal' as type, amount, status, created_at as date 
             FROM withdrawals WHERE user_id = ?)
            ORDER BY date DESC LIMIT 10
        ");
        $stmt->execute([$user_id, $user_id, $user_id]);
        $user_transactions = $stmt->fetchAll();
        
        // Mevcut tüm GPU'ları getir (ekleme için)
        $available_gpus = $pdo->query("SELECT * FROM gpus WHERE is_active = 1 ORDER BY name")->fetchAll();
    }
}

?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['language']; ?>" data-theme="<?php echo getCurrentTheme(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .user-edit-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .user-edit-content {
            background: white;
            border-radius: 10px;
            width: 90%;
            max-width: 1000px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-body {
            padding: 20px;
        }
        .close-modal {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #999;
        }
        .close-modal:hover {
            color: #333;
        }
        .user-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .user-stat-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        .user-stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #3498db;
        }
        .tab-container {
            margin-top: 20px;
        }
        .tab-buttons {
            display: flex;
            border-bottom: 2px solid #eee;
            margin-bottom: 20px;
        }
        .tab-button {
            padding: 12px 20px;
            background: none;
            border: none;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
        }
        .tab-button.active {
            border-bottom-color: #3498db;
            color: #3498db;
            font-weight: bold;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .gpu-list-mini {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .gpu-item-mini {
            background: white;
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            position: relative;
        }
        .gpu-actions {
            position: absolute;
            top: 10px;
            right: 10px;
            display: flex;
            gap: 5px;
        }
        .gpu-action-btn {
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 4px;
            width: 30px;
            height: 30px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }
        .gpu-action-btn:hover {
            background: #c0392b;
        }
        .add-gpu-form {
            background: #e7f3ff;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        .transaction-list {
            max-height: 300px;
            overflow-y: auto;
        }
        .transaction-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .balance-form {
            background: #e7f3ff;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }
        .form-row .form-group {
            flex: 1;
        }
        .delete-confirm {
            background: #ffe6e6;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            border-left: 4px solid #e74c3c;
        }
        .gpu-total-income {
            background: #27ae60;
            color: white;
            padding: 10px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 15px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>⚡ Admin Panel</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="index.php" class="nav-item">
                    <span class="nav-icon">📊</span>
                    <span class="nav-text">Dashboard</span>
                </a>
                <a href="users.php" class="nav-item active">
                    <span class="nav-icon">👥</span>
                    <span class="nav-text">Kullanıcılar</span>
                </a>
                <a href="gpus.php" class="nav-item">
                    <span class="nav-icon">🖥️</span>
                    <span class="nav-text">Ekran Kartları</span>
                </a>
                <a href="investments.php" class="nav-item">
                    <span class="nav-icon">💰</span>
                    <span class="nav-text">Yatırımlar</span>
                </a>
                <a href="commissions.php" class="nav-item">
                    <span class="nav-icon">📈</span>
                    <span class="nav-text">Komisyonlar</span>
                </a>
                <a href="payment_methods.php" class="nav-item">
                    <span class="nav-icon">💳</span>
                    <span class="nav-text">Ödeme Yöntemleri</span>
                </a>
                <a href="settings.php" class="nav-item">
                    <span class="nav-icon">⚙️</span>
                    <span class="nav-text">Ayarlar</span>
                </a>
                <a href="../dashboard.php" class="nav-item">
                    <span class="nav-icon">🏠</span>
                    <span class="nav-text">Siteye Dön</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="admin-main">
            <header class="admin-header">
                <h1>Kullanıcı Yönetimi</h1>
                <div class="admin-actions">
                    <a href="?action=add" class="btn btn-primary">Yeni Kullanıcı Ekle</a>
                    <a href="../includes/logout.php" class="btn btn-sm btn-danger">Çıkış Yap</a>
                </div>
            </header>

            <div class="admin-content">
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="content-card">
                    <div class="table-header">
                        <h3>Tüm Kullanıcılar</h3>
                        <div class="table-actions">
                            <input type="text" placeholder="Kullanıcı ara..." class="search-input" id="userSearch">
                        </div>
                    </div>
                    
                    <div class="data-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Kullanıcı Adı</th>
                                    <th>E-posta</th>
                                    <th>Bakiye</th>
                                    <th>Rol</th>
                                    <th>Kayıt Tarihi</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td>
                                        <strong><?php echo $user['username']; ?></strong>
                                        <?php if ($user['role'] == 'admin'): ?>
                                            <span class="badge badge-primary">Admin</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $user['email']; ?></td>
                                    <td><?php echo formatPrice($user['balance']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $user['role'] == 'admin' ? 'badge-primary' : 'badge-secondary'; ?>">
                                            <?php echo $user['role'] == 'admin' ? 'Admin' : 'Kullanıcı'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-sm btn-primary" onclick="openUserEdit(<?php echo $user['id']; ?>)">
                                                Düzenle
                                            </button>
                                            <?php if ($user['role'] != 'admin'): ?>
                                                <a href="?delete=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Bu kullanıcıyı ve tüm verilerini silmek istediğinizden emin misiniz?')">
                                                    Sil
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Kullanıcı Düzenleme Modal -->
    <?php if ($selected_user): ?>
    <div class="user-edit-modal" id="userEditModal" style="display: flex;">
        <div class="user-edit-content">
            <div class="modal-header">
                <h2>Kullanıcı Düzenle: <?php echo $selected_user['username']; ?></h2>
                <button class="close-modal" onclick="closeUserEdit()">&times;</button>
            </div>
            <div class="modal-body">
                <!-- Kullanıcı İstatistikleri -->
                <div class="user-stats-grid">
                    <div class="user-stat-card">
                        <div>Toplam Bakiye</div>
                        <div class="user-stat-value"><?php echo formatPrice($selected_user['balance']); ?></div>
                    </div>
                    <div class="user-stat-card">
                        <div>Ekran Kartları</div>
                        <div class="user-stat-value"><?php echo count($user_gpus); ?></div>
                    </div>
                    <div class="user-stat-card">
                        <div>Toplam İşlem</div>
                        <div class="user-stat-value"><?php echo count($user_transactions); ?></div>
                    </div>
                    <div class="user-stat-card">
                        <div>Kayıt Tarihi</div>
                        <div class="user-stat-value"><?php echo date('d.m.Y', strtotime($selected_user['created_at'])); ?></div>
                    </div>
                </div>

                <!-- Tab Menü -->
                <div class="tab-container">
                    <div class="tab-buttons">
                        <button class="tab-button active" onclick="openTab('profile-tab')">Profil Bilgileri</button>
                        <button class="tab-button" onclick="openTab('balance-tab')">Bakiye Yönetimi</button>
                        <button class="tab-button" onclick="openTab('gpus-tab')">Ekran Kartları</button>
                        <button class="tab-button" onclick="openTab('transactions-tab')">Son İşlemler</button>
                    </div>

                    <!-- Profil Bilgileri Tab -->
                    <div id="profile-tab" class="tab-content active">
                        <form method="POST">
                            <input type="hidden" name="user_id" value="<?php echo $selected_user['id']; ?>">
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="username">Kullanıcı Adı:</label>
                                    <input type="text" id="username" name="username" required 
                                           value="<?php echo htmlspecialchars($selected_user['username']); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="email">E-posta Adresi:</label>
                                    <input type="email" id="email" name="email" required 
                                           value="<?php echo htmlspecialchars($selected_user['email']); ?>">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="balance">Bakiye:</label>
                                <input type="number" id="balance" name="balance" step="0.01" 
                                       value="<?php echo $selected_user['balance']; ?>">
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" name="update_user" class="btn btn-primary">Bilgileri Güncelle</button>
                                <button type="button" class="btn btn-secondary" onclick="closeUserEdit()">İptal</button>
                            </div>
                        </form>
                    </div>

                    <!-- Bakiye Yönetimi Tab -->
                    <div id="balance-tab" class="tab-content">
                        <div class="balance-form">
                            <h4>Bakiye Düzenle</h4>
                            <form method="POST">
                                <input type="hidden" name="user_id" value="<?php echo $selected_user['id']; ?>">
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="balance_change">Miktar:</label>
                                        <input type="number" id="balance_change" name="balance_change" 
                                               step="0.01" min="0" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="change_type">İşlem Türü:</label>
                                        <select id="change_type" name="change_type" required>
                                            <option value="add">Bakiye Ekle</option>
                                            <option value="subtract">Bakiye Çıkar</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="reason">Açıklama:</label>
                                    <input type="text" id="reason" name="reason" 
                                           placeholder="Bakiye değişikliği açıklaması">
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" name="update_balance" class="btn btn-primary">Bakiyeyi Güncelle</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Ekran Kartları Tab -->
                    <div id="gpus-tab" class="tab-content">
                        <h4>Kullanıcının Ekran Kartları</h4>
                        
                        <!-- Toplam Aylık Gelir -->
                        <?php if (!empty($user_gpus)): 
                            $total_monthly_income = 0;
                            foreach ($user_gpus as $gpu) {
                                $total_monthly_income += $gpu['monthly_income'];
                            }
                        ?>
                        <div class="gpu-total-income">
                            📊 Toplam Aylık Gelir: <?php echo formatPrice($total_monthly_income); ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (empty($user_gpus)): ?>
                            <p class="no-data">Kullanıcının henüz ekran kartı bulunmuyor.</p>
                        <?php else: ?>
                            <div class="gpu-list-mini">
                                <?php foreach ($user_gpus as $gpu): ?>
                                <div class="gpu-item-mini">
                                    <div class="gpu-actions">
                                        <a href="?delete_gpu=<?php echo $gpu['id']; ?>&user_id=<?php echo $selected_user['id']; ?>" 
                                           class="gpu-action-btn"
                                           onclick="return confirm('Bu ekran kartını kullanıcıdan silmek istediğinizden emin misiniz?')"
                                           title="Ekran Kartını Sil">
                                            🗑️
                                        </a>
                                    </div>
                                    <h5><?php echo $gpu['name']; ?></h5>
                                    <p><strong>Fiyat:</strong> <?php echo formatPrice($gpu['price']); ?></p>
                                    <p><strong>Aylık Gelir:</strong> <?php echo formatPrice($gpu['monthly_income']); ?></p>
                                    <small>Satın Alma: <?php echo date('d.m.Y', strtotime($gpu['purchase_date'])); ?></small>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Yeni Ekran Kartı Ekleme Formu -->
                        <div class="add-gpu-form">
                            <h4>➕ Yeni Ekran Kartı Ekle</h4>
                            <form method="POST">
                                <input type="hidden" name="user_id" value="<?php echo $selected_user['id']; ?>">
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="gpu_id">Ekran Kartı Seçin:</label>
                                        <select id="gpu_id" name="gpu_id" required>
                                            <option value="">Ekran kartı seçin...</option>
                                            <?php foreach ($available_gpus as $gpu): ?>
                                            <option value="<?php echo $gpu['id']; ?>">
                                                <?php echo $gpu['name']; ?> - <?php echo formatPrice($gpu['price']); ?> (Aylık: <?php echo formatPrice($gpu['monthly_income']); ?>)
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" name="add_gpu" class="btn btn-primary">Ekran Kartını Ekle</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Son İşlemler Tab -->
                    <div id="transactions-tab" class="tab-content">
                        <h4>Son İşlemler</h4>
                        <?php if (empty($user_transactions)): ?>
                            <p class="no-data">Henüz işlem bulunmuyor.</p>
                        <?php else: ?>
                            <div class="transaction-list">
                                <?php foreach ($user_transactions as $transaction): ?>
                                <div class="transaction-item">
                                    <div>
                                        <strong>
                                            <?php 
                                            $type_names = [
                                                'investment' => 'Yatırım',
                                                'purchase' => 'GPU Satın Alma',
                                                'withdrawal' => 'Para Çekme'
                                            ];
                                            echo $type_names[$transaction['type']] ?? $transaction['type'];
                                            ?>
                                        </strong>
                                        <div class="text-muted">
                                            <?php echo date('d.m.Y H:i', strtotime($transaction['date'])); ?>
                                        </div>
                                    </div>
                                    <div>
                                        <span class="<?php echo $transaction['type'] == 'withdrawal' ? 'text-danger' : 'text-success'; ?>">
                                            <?php echo $transaction['type'] == 'withdrawal' ? '-' : '+'; ?>
                                            <?php echo formatPrice($transaction['amount']); ?>
                                        </span>
                                        <div class="badge badge-<?php echo $transaction['status'] == 'approved' ? 'success' : ($transaction['status'] == 'pending' ? 'warning' : 'danger'); ?>">
                                            <?php echo $transaction['status'] == 'approved' ? 'Onaylandı' : ($transaction['status'] == 'pending' ? 'Bekliyor' : 'Reddedildi'); ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Silme Onayı -->
                <?php if ($selected_user['role'] != 'admin'): ?>
                <div class="delete-confirm">
                    <h4>⚠️ Kullanıcıyı Sil</h4>
                    <p>Bu kullanıcıyı ve tüm verilerini (GPU'lar, işlemler) kalıcı olarak silmek istiyor musunuz?</p>
                    <a href="?delete=<?php echo $selected_user['id']; ?>" class="btn btn-danger" 
                       onclick="return confirm('Bu işlem geri alınamaz! Devam etmek istediğinizden emin misiniz?')">
                        Kullanıcıyı Sil
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="../assets/js/admin.js"></script>
    <script>
    // Kullanıcı arama
    document.getElementById('userSearch').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = document.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            if (text.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    // Tab değiştirme
    function openTab(tabId) {
        // Tüm tab içeriklerini gizle
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Tüm tab butonlarını pasif yap
        document.querySelectorAll('.tab-button').forEach(button => {
            button.classList.remove('active');
        });
        
        // Seçili tab'ı aktif yap
        document.getElementById(tabId).classList.add('active');
        event.target.classList.add('active');
    }

    // Kullanıcı düzenleme modal'ını aç
    function openUserEdit(userId) {
        window.location.href = '?edit=' + userId;
    }

    // Kullanıcı düzenleme modal'ını kapat
    function closeUserEdit() {
        window.location.href = 'users.php';
    }

    // Modal dışına tıklayınca kapat
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('user-edit-modal')) {
            closeUserEdit();
        }
    });
    </script>
</body>
</html>