<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// ADMIN GİRİŞ KONTROLÜ - Eğer giriş yapılmamışsa admin login'e yönlendir
if (!isLoggedIn()) {
    redirect('login.php');
}

// Eğer admin değilse ana siteye yönlendir
if (!isAdmin()) {
    redirect('../dashboard.php');
}

$page_title = __('admin_panel');
$admin_page = true;

// İstatistikler
$total_users = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$total_gpus = $pdo->query("SELECT COUNT(*) FROM gpus")->fetchColumn();
$total_investment = $pdo->query("SELECT SUM(amount) FROM investments WHERE status = 'approved'")->fetchColumn() ?: 0;
$pending_approvals = $pdo->query("SELECT COUNT(*) FROM investments WHERE status = 'pending'")->fetchColumn() + 
                     $pdo->query("SELECT COUNT(*) FROM purchases WHERE status = 'pending'")->fetchColumn();

// Çekim talebi istatistikleri
$pending_withdrawals = $pdo->query("SELECT COUNT(*) FROM withdrawals WHERE status = 'pending'")->fetchColumn();
$total_withdrawals = $pdo->query("SELECT COUNT(*) FROM withdrawals")->fetchColumn();

// Son işlemler - DÜZELTİLMİŞ SQL SORGUSU
$stmt = $pdo->query("
    (SELECT 'investment' as type, amount, status, investment_date as date, u.username 
     FROM investments i JOIN users u ON i.user_id = u.id ORDER BY investment_date DESC LIMIT 5)
    UNION
    (SELECT 'purchase' as type, amount, status, purchase_date as date, u.username 
     FROM purchases p JOIN users u ON p.user_id = u.id ORDER BY purchase_date DESC LIMIT 5)
    UNION
    (SELECT 'withdrawal' as type, amount, status, w.created_at as date, u.username 
     FROM withdrawals w JOIN users u ON w.user_id = u.id ORDER BY w.created_at DESC LIMIT 5)
    ORDER BY date DESC LIMIT 10
");
$recent_activities = $stmt->fetchAll();
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
        /* Nav badge */
        .nav-badge {
            background: #e74c3c;
            color: white;
            border-radius: 10px;
            padding: 2px 6px;
            font-size: 0.7rem;
            font-weight: bold;
            margin-left: auto;
        }

        /* Action badge */
        .action-badge {
            background: #e74c3c;
            color: white;
            border-radius: 10px;
            padding: 2px 6px;
            font-size: 0.7rem;
            font-weight: bold;
            position: absolute;
            top: -5px;
            right: -5px;
        }

        .action-card.admin {
            position: relative;
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
                <a href="index.php" class="nav-item active">
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
                <a href="withdrawals.php" class="nav-item">
                    <span class="nav-icon">📤</span>
                    <span class="nav-text">Çekim Talepleri</span>
                    <?php if ($pending_withdrawals > 0): ?>
                        <span class="nav-badge"><?php echo $pending_withdrawals; ?></span>
                    <?php endif; ?>
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
                <h1>Admin Dashboard</h1>
                <div class="admin-actions">
                    <span>Hoş geldiniz, <strong><?php echo $_SESSION['username']; ?></strong> (Admin)</span>
                    <a href="../includes/logout.php" class="btn btn-sm btn-danger">Çıkış Yap</a>
                </div>
            </header>

            <div class="admin-content">
                <!-- İstatistik Kartları -->
                <div class="stats-grid admin">
                    <div class="stat-card admin">
                        <div class="stat-icon">👥</div>
                        <div class="stat-info">
                            <h3>Toplam Kullanıcı</h3>
                            <p class="stat-value"><?php echo $total_users; ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-card admin">
                        <div class="stat-icon">🖥️</div>
                        <div class="stat-info">
                            <h3>Toplam GPU</h3>
                            <p class="stat-value"><?php echo $total_gpus; ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-card admin">
                        <div class="stat-icon">💰</div>
                        <div class="stat-info">
                            <h3>Toplam Yatırım</h3>
                            <p class="stat-value"><?php echo formatPrice($total_investment); ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-card admin">
                        <div class="stat-icon">📤</div>
                        <div class="stat-info">
                            <h3>Bekleyen Çekimler</h3>
                            <p class="stat-value"><?php echo $pending_withdrawals; ?></p>
                            <small>Toplam: <?php echo $total_withdrawals; ?> çekim</small>
                        </div>
                    </div>
                </div>

                <!-- Son Aktiviteler -->
                <div class="content-row">
                    <div class="content-col full">
                        <div class="content-card">
                            <h3>Son Aktiviteler</h3>
                            <?php if (empty($recent_activities)): ?>
                                <p class="no-data">Henüz aktivite bulunmuyor</p>
                            <?php else: ?>
                                <div class="activity-list">
                                    <?php foreach ($recent_activities as $activity): ?>
                                        <div class="activity-item">
                                            <div class="activity-icon">
                                                <?php 
                                                switch($activity['type']) {
                                                    case 'investment': echo '💰'; break;
                                                    case 'purchase': echo '🖥️'; break;
                                                    case 'withdrawal': echo '📤'; break;
                                                    default: echo '📄';
                                                }
                                                ?>
                                            </div>
                                            <div class="activity-info">
                                                <div class="activity-main">
                                                    <strong><?php echo $activity['username']; ?></strong>
                                                    <?php 
                                                    switch($activity['type']) {
                                                        case 'investment': echo ' yatırım yaptı'; break;
                                                        case 'purchase': echo ' ekran kartı satın aldı'; break;
                                                        case 'withdrawal': echo ' para çekme talebi oluşturdu'; break;
                                                    }
                                                    ?>
                                                    <strong><?php echo formatPrice($activity['amount']); ?></strong>
                                                </div>
                                                <div class="activity-meta">
                                                    <span class="activity-date"><?php echo date('d.m.Y H:i', strtotime($activity['date'])); ?></span>
                                                    <span class="activity-status status-<?php echo $activity['status']; ?>">
                                                        <?php 
                                                        switch($activity['status']) {
                                                            case 'pending': echo 'Beklemede'; break;
                                                            case 'approved': echo 'Onaylandı'; break;
                                                            case 'rejected': echo 'Reddedildi'; break;
                                                            default: echo $activity['status'];
                                                        }
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Hızlı İşlemler -->
                <div class="quick-actions admin">
                    <h3>Hızlı İşlemler</h3>
                    <div class="action-grid admin">
                        <a href="users.php?action=add" class="action-card admin">
                            <div class="action-icon">➕</div>
                            <span>Kullanıcı Ekle</span>
                        </a>
                        <a href="gpus.php?action=add" class="action-card admin">
                            <div class="action-icon">🖥️</div>
                            <span>GPU Ekle</span>
                        </a>
                        <a href="investments.php" class="action-card admin">
                            <div class="action-icon">✅</div>
                            <span>Yatırımları Onayla</span>
                        </a>
                        <a href="withdrawals.php" class="action-card admin">
                            <div class="action-icon">📤</div>
                            <span>Çekim Talepleri</span>
                            <?php if ($pending_withdrawals > 0): ?>
                                <span class="action-badge"><?php echo $pending_withdrawals; ?></span>
                            <?php endif; ?>
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/admin.js"></script>
</body>
</html>