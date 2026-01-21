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

$page_title = "Yatırım ve Satın Alma Yönetimi";
$admin_page = true;

// Tüm işlemleri getir (yatırımlar + satın alımlar) - DÜZELTİLMİŞ SORGULAR
$investments = $pdo->query("
    SELECT 
        'investment' as type,
        i.id,
        i.user_id,
        u.username,
        u.email,
        i.amount,
        i.status,
        i.investment_date as date,
        i.screenshot_path,
        pm.coin_name,
        NULL as gpu_name,
        NULL as gpu_id
     FROM investments i 
     JOIN users u ON i.user_id = u.id 
     LEFT JOIN payment_methods pm ON i.payment_method_id = pm.id
     ORDER BY i.investment_date DESC
")->fetchAll();

$purchases = $pdo->query("
    SELECT 
        'purchase' as type,
        p.id,
        p.user_id,
        u.username,
        u.email,
        p.amount,
        p.status,
        p.purchase_date as date,
        p.screenshot_path,
        pm.coin_name,
        g.name as gpu_name,
        p.gpu_id
     FROM purchases p 
     JOIN users u ON p.user_id = u.id 
     JOIN gpus g ON p.gpu_id = g.id 
     LEFT JOIN payment_methods pm ON p.payment_method_id = pm.id
     ORDER BY p.purchase_date DESC
")->fetchAll();

// Tüm işlemleri birleştir
$transactions = array_merge($investments, $purchases);

// Tarihe göre sırala
usort($transactions, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

// İşlem durumu güncelleme
if (isset($_POST['update_status'])) {
    $transaction_id = $_POST['transaction_id'];
    $transaction_type = $_POST['transaction_type'];
    $status = $_POST['status'];
    
    try {
        $pdo->beginTransaction();
        
        if ($transaction_type == 'investment') {
            // Yatırım durumu güncelle
            $stmt = $pdo->prepare("UPDATE investments SET status = ? WHERE id = ?");
            $stmt->execute([$status, $transaction_id]);
            
            // Eğer onaylandıysa, kullanıcının YATIRIM bakiyesini güncelle
            if ($status == 'approved') {
                $stmt = $pdo->prepare("SELECT user_id, amount FROM investments WHERE id = ?");
                $stmt->execute([$transaction_id]);
                $investment = $stmt->fetch();
                
                if ($investment) {
                    $stmt = $pdo->prepare("UPDATE users SET investment_balance = investment_balance + ? WHERE id = ?");
                    $stmt->execute([$investment['amount'], $investment['user_id']]);
                    
                    // Kullanıcının yeni yatırım bakiyesini al
                    $new_balance_stmt = $pdo->prepare("SELECT investment_balance FROM users WHERE id = ?");
                    $new_balance_stmt->execute([$investment['user_id']]);
                    $new_balance = $new_balance_stmt->fetch()['investment_balance'];
                    
                    $success = "Yatırım onaylandı! Kullanıcının yatırım bakiyesi güncellendi. Yeni bakiye: " . formatPrice($new_balance);
                }
            } elseif ($status == 'rejected') {
                $success = "Yatırım reddedildi!";
            } else {
                $success = "Yatırım durumu güncellendi!";
            }
            
        } elseif ($transaction_type == 'purchase') {
            // Satın alma durumu güncelle
            $stmt = $pdo->prepare("UPDATE purchases SET status = ? WHERE id = ?");
            $stmt->execute([$status, $transaction_id]);
            
            // Eğer onaylandıysa, kullanıcının GPU'sunu ekle
            if ($status == 'approved') {
                $stmt = $pdo->prepare("SELECT user_id, gpu_id, id as purchase_id, amount FROM purchases WHERE id = ?");
                $stmt->execute([$transaction_id]);
                $purchase = $stmt->fetch();
                
                if ($purchase) {
                    // Kullanıcının yatırım bakiyesini kontrol et
                    $balance_stmt = $pdo->prepare("SELECT investment_balance FROM users WHERE id = ?");
                    $balance_stmt->execute([$purchase['user_id']]);
                    $user_balance = $balance_stmt->fetch()['investment_balance'];
                    
                    if ($user_balance >= $purchase['amount']) {
                        // YATIRIM bakiyesinden düş
                        $stmt = $pdo->prepare("UPDATE users SET investment_balance = investment_balance - ? WHERE id = ?");
                        $stmt->execute([$purchase['amount'], $purchase['user_id']]);
                        
                        // user_gpus tablosuna ekle (eğer daha önce eklenmediyse)
                        $check_stmt = $pdo->prepare("SELECT id FROM user_gpus WHERE purchase_id = ?");
                        $check_stmt->execute([$purchase['purchase_id']]);
                        
                        if ($check_stmt->rowCount() == 0) {
                            $stmt = $pdo->prepare("INSERT INTO user_gpus (user_id, gpu_id, purchase_id) VALUES (?, ?, ?)");
                            $stmt->execute([$purchase['user_id'], $purchase['gpu_id'], $purchase['purchase_id']]);
                            
                            // GPU'yu aktif et - 24 saat geri sayım başlasın
                            $activation_result = activateGPU($purchase['purchase_id'], $purchase['gpu_id'], $purchase['user_id']);
                            
                            if ($activation_result) {
                                $success = "Satın alma onaylandı! GPU aktif edildi. Kazançlar 24 saat sonra başlayacak. Kullanıcının yatırım bakiyesinden " . formatPrice($purchase['amount']) . " düşüldü.";
                            } else {
                                $success = "Satın alma onaylandı! GPU eklenirken aktivasyon hatası.";
                            }
                        } else {
                            $success = "Satın alma zaten onaylanmış!";
                        }
                    } else {
                        throw new Exception("Kullanıcının yatırım bakiyesi yetersiz! Gerekli: " . formatPrice($purchase['amount']) . ", Mevcut: " . formatPrice($user_balance));
                    }
                }
                
            } elseif ($status == 'rejected') {
                // Reddedildiğinde stoku geri ver
                $stmt = $pdo->prepare("SELECT gpu_id FROM purchases WHERE id = ?");
                $stmt->execute([$transaction_id]);
                $purchase = $stmt->fetch();
                
                if ($purchase) {
                    $stmt = $pdo->prepare("UPDATE gpus SET stock = stock + 1 WHERE id = ?");
                    $stmt->execute([$purchase['gpu_id']]);
                    
                    $success = "Satın alma reddedildi ve stok geri verildi!";
                }
            } else {
                $success = "Satın alma durumu güncellendi!";
            }
        }
        
        $pdo->commit();
        
        // Sayfayı yenile
        header("Location: investments.php");
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "İşlem sırasında hata oluştu: " . $e->getMessage();
    }
}

// İstatistikler
$pending_count = $pdo->query("
    SELECT COUNT(*) FROM (
        SELECT id FROM investments WHERE status = 'pending'
        UNION ALL
        SELECT id FROM purchases WHERE status = 'pending'
    ) as pending
")->fetchColumn();

$approved_count = $pdo->query("
    SELECT COUNT(*) FROM (
        SELECT id FROM investments WHERE status = 'approved'
        UNION ALL
        SELECT id FROM purchases WHERE status = 'approved'
    ) as approved
")->fetchColumn();

$total_investment_amount = $pdo->query("
    SELECT SUM(amount) FROM investments WHERE status = 'approved'
")->fetchColumn() ?: 0;

$total_purchase_amount = $pdo->query("
    SELECT SUM(amount) FROM purchases WHERE status = 'approved'
")->fetchColumn() ?: 0;

$total_amount = $total_investment_amount + $total_purchase_amount;
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
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d1edff; color: #0c5460; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .type-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            background: #e9ecef;
            color: #495057;
        }
        .type-investment { background: #d1edff; color: #0c5460; }
        .type-purchase { background: #d4edda; color: #155724; }
        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        .action-buttons form {
            margin: 0;
        }
        .transaction-details {
            font-size: 0.9rem;
            color: #666;
            margin-top: 5px;
        }
        .gpu-status-info {
            font-size: 0.8rem;
            color: #666;
            margin-top: 2px;
        }
        .balance-info {
            font-size: 0.8rem;
            color: #3498db;
            margin-top: 2px;
            font-weight: 500;
        }
        .user-balance-info {
            background: #f8f9fa;
            padding: 8px;
            border-radius: 6px;
            margin: 5px 0;
            border-left: 3px solid #3498db;
        }
        .balance-label {
            font-weight: 600;
            color: #2c3e50;
        }
        .investment-balance { color: #3498db; }
        .earnings-balance { color: #27ae60; }
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
                <a href="users.php" class="nav-item">
                    <span class="nav-icon">👥</span>
                    <span class="nav-text">Kullanıcılar</span>
                </a>
                <a href="gpus.php" class="nav-item">
                    <span class="nav-icon">🖥️</span>
                    <span class="nav-text">Ekran Kartları</span>
                </a>
                <a href="investments.php" class="nav-item active">
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
                <h1>Yatırım ve Satın Alma Yönetimi</h1>
                <div class="admin-actions">
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

                <!-- İstatistik Kartları -->
                <div class="stats-grid admin">
                    <div class="stat-card admin">
                        <div class="stat-icon">⏳</div>
                        <div class="stat-info">
                            <h3>Bekleyen İşlemler</h3>
                            <p class="stat-value"><?php echo $pending_count; ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-card admin">
                        <div class="stat-icon">✅</div>
                        <div class="stat-info">
                            <h3>Onaylanan İşlemler</h3>
                            <p class="stat-value"><?php echo $approved_count; ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-card admin">
                        <div class="stat-icon">💰</div>
                        <div class="stat-info">
                            <h3>Toplam Yatırım</h3>
                            <p class="stat-value"><?php echo formatPrice($total_investment_amount); ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-card admin">
                        <div class="stat-icon">🖥️</div>
                        <div class="stat-info">
                            <h3>Toplam Satın Alma</h3>
                            <p class="stat-value"><?php echo formatPrice($total_purchase_amount); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Tüm İşlemler -->
                <div class="content-card">
                    <div class="table-header">
                        <h3>Tüm Yatırım ve Satın Alma İşlemleri</h3>
                        <div class="table-actions">
                            <select id="typeFilter" class="filter-select">
                                <option value="">Tüm İşlemler</option>
                                <option value="investment">Yatırımlar</option>
                                <option value="purchase">Satın Alımlar</option>
                            </select>
                            <select id="statusFilter" class="filter-select">
                                <option value="">Tüm Durumlar</option>
                                <option value="pending">Bekleyen</option>
                                <option value="approved">Onaylanan</option>
                                <option value="rejected">Reddedilen</option>
                            </select>
                            <input type="text" placeholder="Kullanıcı ara..." class="search-input" id="searchInput">
                        </div>
                    </div>
                    
                    <div class="data-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>İşlem Türü</th>
                                    <th>Kullanıcı</th>
                                    <th>Detaylar</th>
                                    <th>Miktar</th>
                                    <th>Tarih</th>
                                    <th>Durum</th>
                                    <th>Ekran Görüntüsü</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $transaction): 
                                    // Kullanıcı bakiyelerini getir
                                    $user_balances = $pdo->prepare("SELECT investment_balance, earnings_balance FROM users WHERE id = ?");
                                    $user_balances->execute([$transaction['user_id']]);
                                    $balances = $user_balances->fetch();
                                ?>
                                <tr data-type="<?php echo $transaction['type']; ?>" data-status="<?php echo $transaction['status']; ?>" data-user="<?php echo strtolower($transaction['username']); ?>">
                                    <td><?php echo $transaction['id']; ?></td>
                                    <td>
                                        <span class="type-badge type-<?php echo $transaction['type']; ?>">
                                            <?php echo $transaction['type'] == 'investment' ? '💰 Yatırım' : '🖥️ Satın Alma'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?php echo $transaction['username']; ?></strong>
                                        <div class="text-muted"><?php echo $transaction['email']; ?></div>
                                        <div class="user-balance-info">
                                            <div class="balance-info">
                                                <span class="balance-label">Yatırım:</span> 
                                                <span class="investment-balance"><?php echo formatPrice($balances['investment_balance']); ?></span>
                                            </div>
                                            <div class="balance-info">
                                                <span class="balance-label">Kazanç:</span> 
                                                <span class="earnings-balance"><?php echo formatPrice($balances['earnings_balance']); ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($transaction['type'] == 'purchase'): ?>
                                            <strong><?php echo $transaction['gpu_name']; ?></strong>
                                            <div class="transaction-details">
                                                GPU Satın Alma
                                            </div>
                                            <?php 
                                            // GPU aktivasyon durumunu göster
                                            if ($transaction['status'] == 'approved') {
                                                $gpu_status = $pdo->prepare("
                                                    SELECT activation_time, 
                                                           TIMESTAMPDIFF(HOUR, activation_time, NOW()) as hours_passed 
                                                    FROM user_gpus 
                                                    WHERE purchase_id = ?
                                                ");
                                                $gpu_status->execute([$transaction['id']]);
                                                $gpu_info = $gpu_status->fetch();
                                                
                                                if ($gpu_info && $gpu_info['activation_time']) {
                                                    if ($gpu_info['hours_passed'] < 24) {
                                                        echo '<div class="gpu-status-info">⏳ ' . (24 - $gpu_info['hours_passed']) . ' saat sonra kazanç başlayacak</div>';
                                                    } else {
                                                        echo '<div class="gpu-status-info">✅ Kazanç aktif</div>';
                                                    }
                                                }
                                            }
                                            ?>
                                        <?php else: ?>
                                            <strong>Para Yatırma</strong>
                                            <div class="transaction-details">
                                                <?php echo $transaction['coin_name'] ?: 'N/A'; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo formatPrice($transaction['amount']); ?></td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($transaction['date'])); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $transaction['status']; ?>">
                                            <?php echo $transaction['status'] == 'pending' ? 'Bekleyen' : ($transaction['status'] == 'approved' ? 'Onaylandı' : 'Reddedildi'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($transaction['screenshot_path']): ?>
                                            <a href="../<?php echo $transaction['screenshot_path']; ?>" target="_blank" class="btn btn-sm btn-secondary">Görüntüle</a>
                                        <?php else: ?>
                                            <span class="text-muted">Yok</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if ($transaction['status'] == 'pending'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="transaction_id" value="<?php echo $transaction['id']; ?>">
                                                    <input type="hidden" name="transaction_type" value="<?php echo $transaction['type']; ?>">
                                                    <input type="hidden" name="status" value="approved">
                                                    <button type="submit" name="update_status" class="btn btn-sm btn-success" 
                                                            onclick="return confirm('Bu işlemi onaylamak istediğinizden emin misiniz?')">
                                                        ✅ Onayla
                                                    </button>
                                                </form>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="transaction_id" value="<?php echo $transaction['id']; ?>">
                                                    <input type="hidden" name="transaction_type" value="<?php echo $transaction['type']; ?>">
                                                    <input type="hidden" name="status" value="rejected">
                                                    <button type="submit" name="update_status" class="btn btn-sm btn-danger"
                                                            onclick="return confirm('Bu işlemi reddetmek istediğinizden emin misiniz?')">
                                                        ❌ Reddet
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-muted">İşlem tamamlandı</span>
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

    <script src="../assets/js/admin.js"></script>
    <script>
    // Filtreleme işlevi
    document.getElementById('typeFilter').addEventListener('change', filterTable);
    document.getElementById('statusFilter').addEventListener('change', filterTable);
    document.getElementById('searchInput').addEventListener('input', filterTable);
    
    function filterTable() {
        const typeFilter = document.getElementById('typeFilter').value;
        const statusFilter = document.getElementById('statusFilter').value;
        const searchFilter = document.getElementById('searchInput').value.toLowerCase();
        const rows = document.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const type = row.getAttribute('data-type');
            const status = row.getAttribute('data-status');
            const user = row.getAttribute('data-user');
            const text = row.textContent.toLowerCase();
            
            const typeMatch = !typeFilter || type === typeFilter;
            const statusMatch = !statusFilter || status === statusFilter;
            const searchMatch = !searchFilter || user.includes(searchFilter) || text.includes(searchFilter);
            
            if (typeMatch && statusMatch && searchMatch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
    </script>
</body>
</html>