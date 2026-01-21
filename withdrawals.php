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

$page_title = "Çekim Talepleri";
$admin_page = true;

// Çekim talebi onaylama
if (isset($_GET['approve'])) {
    $withdrawal_id = intval($_GET['approve']);
    
    try {
        // Çekim talebini onayla
        $stmt = $pdo->prepare("UPDATE withdrawals SET status = 'approved' WHERE id = ?");
        if ($stmt->execute([$withdrawal_id])) {
            $success = "Çekim talebi başarıyla onaylandı!";
        } else {
            $error = "Çekim talebi onaylanırken hata oluştu!";
        }
    } catch (Exception $e) {
        $error = "Hata: " . $e->getMessage();
    }
    
    // Sayfayı yenile
    header("Location: withdrawals.php");
    exit;
}

// Çekim talebi reddetme
if (isset($_GET['reject'])) {
    $withdrawal_id = intval($_GET['reject']);
    
    try {
        // Çekim talebini reddet ve bakiyeyi iade et
        $stmt = $pdo->prepare("
            SELECT w.*, u.username 
            FROM withdrawals w 
            JOIN users u ON w.user_id = u.id 
            WHERE w.id = ?
        ");
        $stmt->execute([$withdrawal_id]);
        $withdrawal = $stmt->fetch();
        
        if ($withdrawal) {
            // Bakiyeyi iade et
            $update_stmt = $pdo->prepare("UPDATE users SET earnings_balance = earnings_balance + ? WHERE id = ?");
            $update_stmt->execute([$withdrawal['amount'], $withdrawal['user_id']]);
            
            // Çekim talebini reddet
            $stmt = $pdo->prepare("UPDATE withdrawals SET status = 'rejected' WHERE id = ?");
            if ($stmt->execute([$withdrawal_id])) {
                $success = "Çekim talebi reddedildi ve bakiye kullanıcıya iade edildi!";
            }
        }
    } catch (Exception $e) {
        $error = "Hata: " . $e->getMessage();
    }
    
    // Sayfayı yenile
    header("Location: withdrawals.php");
    exit;
}

// Tüm çekim taleplerini getir
$withdrawals = $pdo->query("
    SELECT w.*, u.username, u.email 
    FROM withdrawals w 
    JOIN users u ON w.user_id = u.id 
    ORDER BY w.created_at DESC
")->fetchAll();

// İstatistikler
$total_withdrawals = $pdo->query("SELECT COUNT(*) as total FROM withdrawals")->fetch()['total'];
$pending_withdrawals = $pdo->query("SELECT COUNT(*) as pending FROM withdrawals WHERE status = 'pending'")->fetch()['pending'];
$approved_withdrawals = $pdo->query("SELECT COUNT(*) as approved FROM withdrawals WHERE status = 'approved'")->fetch()['approved'];
$total_amount = $pdo->query("SELECT SUM(amount) as total FROM withdrawals WHERE status = 'approved'")->fetch()['total'] ?: 0;
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
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #3498db;
        }
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        .withdrawal-status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .status-pending {
            background: #f39c12;
            color: white;
        }
        .status-approved {
            background: #27ae60;
            color: white;
        }
        .status-rejected {
            background: #e74c3c;
            color: white;
        }
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.8rem;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #3498db;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        .filter-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .filter-btn {
            padding: 8px 16px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 4px;
            cursor: pointer;
        }
        .filter-btn.active {
            background: #3498db;
            color: white;
            border-color: #3498db;
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
                <a href="users.php" class="nav-item">
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
                <a href="withdrawals.php" class="nav-item active">
                    <span class="nav-icon">📤</span>
                    <span class="nav-text">Çekim Talepleri</span>
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
                <h1>Çekim Talepleri</h1>
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
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">📤</div>
                        <div class="stat-value"><?php echo $total_withdrawals; ?></div>
                        <div class="stat-label">Toplam Çekim</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">⏳</div>
                        <div class="stat-value"><?php echo $pending_withdrawals; ?></div>
                        <div class="stat-label">Bekleyen Talep</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">✅</div>
                        <div class="stat-value"><?php echo $approved_withdrawals; ?></div>
                        <div class="stat-label">Onaylanan</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">💰</div>
                        <div class="stat-value"><?php echo formatPrice($total_amount); ?></div>
                        <div class="stat-label">Toplam Çekilen</div>
                    </div>
                </div>

                <!-- Filtre Butonları -->
                <div class="filter-buttons">
                    <button class="filter-btn active" onclick="filterWithdrawals('all')">Tümü</button>
                    <button class="filter-btn" onclick="filterWithdrawals('pending')">Bekleyen</button>
                    <button class="filter-btn" onclick="filterWithdrawals('approved')">Onaylanan</button>
                    <button class="filter-btn" onclick="filterWithdrawals('rejected')">Reddedilen</button>
                </div>

                <div class="content-card">
                    <div class="table-header">
                        <h3>Tüm Çekim Talepleri</h3>
                        <div class="table-actions">
                            <input type="text" placeholder="Kullanıcı ara..." class="search-input" id="userSearch">
                        </div>
                    </div>
                    
                    <div class="data-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Kullanıcı</th>
                                    <th>Miktar</th>
                                    <th>Cüzdan Adresi</th>
                                    <th>Coin Türü</th>
                                    <th>Talep Tarihi</th>
                                    <th>Durum</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($withdrawals as $withdrawal): ?>
                                <tr data-status="<?php echo $withdrawal['status']; ?>">
                                    <td><?php echo $withdrawal['id']; ?></td>
                                    <td>
                                        <div class="user-info">
                                            <div class="user-avatar">
                                                <?php echo strtoupper(substr($withdrawal['username'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <strong><?php echo $withdrawal['username']; ?></strong>
                                                <div class="text-muted"><?php echo $withdrawal['email']; ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <strong><?php echo formatPrice($withdrawal['amount']); ?></strong>
                                    </td>
                                    <td>
                                        <code style="font-size: 0.8rem;"><?php echo $withdrawal['wallet_address']; ?></code>
                                    </td>
                                    <td>
                                        <span class="badge badge-secondary"><?php echo $withdrawal['coin_type']; ?></span>
                                    </td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($withdrawal['created_at'])); ?></td>
                                    <td>
                                        <span class="withdrawal-status status-<?php echo $withdrawal['status']; ?>">
                                            <?php 
                                            $status_text = [
                                                'pending' => 'Bekliyor',
                                                'approved' => 'Onaylandı',
                                                'rejected' => 'Reddedildi'
                                            ];
                                            echo $status_text[$withdrawal['status']] ?? $withdrawal['status'];
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if ($withdrawal['status'] == 'pending'): ?>
                                                <a href="?approve=<?php echo $withdrawal['id']; ?>" class="btn btn-sm btn-success" 
                                                   onclick="return confirm('Bu çekim talebini onaylamak istediğinizden emin misiniz?')">
                                                    ✅ Onayla
                                                </a>
                                                <a href="?reject=<?php echo $withdrawal['id']; ?>" class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Bu çekim talebini reddetmek istediğinizden emin misiniz? Bakiye kullanıcıya iade edilecek.')">
                                                    ❌ Reddet
                                                </a>
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

    // Çekim talebi filtreleme
    function filterWithdrawals(status) {
        const rows = document.querySelectorAll('tbody tr');
        const filterBtns = document.querySelectorAll('.filter-btn');
        
        // Butonları aktif/pasif yap
        filterBtns.forEach(btn => {
            btn.classList.remove('active');
        });
        event.target.classList.add('active');
        
        // Satırları filtrele
        rows.forEach(row => {
            if (status === 'all') {
                row.style.display = '';
            } else {
                if (row.getAttribute('data-status') === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        });
    }

    // Sayfa yüklendiğinde bekleyen talepleri göster
    document.addEventListener('DOMContentLoaded', function() {
        const pendingCount = <?php echo $pending_withdrawals; ?>;
        if (pendingCount > 0) {
            // Otomatik olarak bekleyen talepleri göster
            filterWithdrawals('pending');
            // Butonu aktif yap
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('active');
                if (btn.textContent.includes('Bekleyen')) {
                    btn.classList.add('active');
                }
            });
        }
    });
    </script>
</body>
</html>