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

$page_title = "Ödeme Yöntemleri Yönetimi";
$admin_page = true;

// Ödeme yöntemlerini getir
$payment_methods = $pdo->query("SELECT * FROM payment_methods ORDER BY id")->fetchAll();

// Ödeme yöntemi ekleme/düzenleme
if ($_POST && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action == 'add' || $action == 'edit') {
        $coin_name = $_POST['coin_name'];
        $wallet_address = $_POST['wallet_address'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if ($action == 'add') {
            $stmt = $pdo->prepare("INSERT INTO payment_methods (coin_name, wallet_address, is_active) VALUES (?, ?, ?)");
            $stmt->execute([$coin_name, $wallet_address, $is_active]);
            $success = "Ödeme yöntemi başarıyla eklendi!";
        } else {
            $id = $_POST['id'];
            $stmt = $pdo->prepare("UPDATE payment_methods SET coin_name=?, wallet_address=?, is_active=? WHERE id=?");
            $stmt->execute([$coin_name, $wallet_address, $is_active, $id]);
            $success = "Ödeme yöntemi başarıyla güncellendi!";
        }
    }
}

// Ödeme yöntemi silme
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM payment_methods WHERE id = ?");
    $stmt->execute([$id]);
    $success = "Ödeme yöntemi başarıyla silindi!";
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
                <a href="commissions.php" class="nav-item">
                    <span class="nav-icon">📈</span>
                    <span class="nav-text">Komisyonlar</span>
                </a>
                <a href="payment_methods.php" class="nav-item active">
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
                <h1>Ödeme Yöntemleri Yönetimi</h1>
                <div class="admin-actions">
                    <a href="?action=add" class="btn btn-primary">Yeni Ödeme Yöntemi Ekle</a>
                    <a href="../includes/logout.php" class="btn btn-sm btn-danger">Çıkış Yap</a>
                </div>
            </header>

            <div class="admin-content">
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <!-- Ödeme Yöntemi Ekleme/Düzenleme Formu -->
                <?php if (isset($_GET['action']) && ($_GET['action'] == 'add' || $_GET['action'] == 'edit')): ?>
                    <?php
                    $method = null;
                    if ($_GET['action'] == 'edit' && isset($_GET['id'])) {
                        $stmt = $pdo->prepare("SELECT * FROM payment_methods WHERE id = ?");
                        $stmt->execute([$_GET['id']]);
                        $method = $stmt->fetch();
                    }
                    ?>
                    <div class="content-card">
                        <h3><?php echo $_GET['action'] == 'add' ? 'Yeni Ödeme Yöntemi Ekle' : 'Ödeme Yöntemi Düzenle'; ?></h3>
                        <form method="POST">
                            <input type="hidden" name="action" value="<?php echo $_GET['action']; ?>">
                            <?php if ($_GET['action'] == 'edit'): ?>
                                <input type="hidden" name="id" value="<?php echo $method['id']; ?>">
                            <?php endif; ?>
                            
                            <div class="form-group">
                                <label for="coin_name">Kripto Para Adı:</label>
                                <input type="text" id="coin_name" name="coin_name" required 
                                       value="<?php echo $method ? $method['coin_name'] : ''; ?>"
                                       placeholder="Örn: Bitcoin, Ethereum, USDT">
                            </div>
                            
                            <div class="form-group">
                                <label for="wallet_address">Cüzdan Adresi:</label>
                                <textarea id="wallet_address" name="wallet_address" rows="3" required
                                          placeholder="Kripto para cüzdan adresiniz"><?php echo $method ? $method['wallet_address'] : ''; ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="is_active" value="1" 
                                           <?php echo ($method && $method['is_active']) || !$method ? 'checked' : ''; ?>>
                                    Aktif
                                </label>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary"><?php echo $_GET['action'] == 'add' ? 'Ekle' : 'Güncelle'; ?></button>
                                <a href="payment_methods.php" class="btn btn-secondary">İptal</a>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <!-- Ödeme Yöntemleri Listesi -->
                    <div class="content-card">
                        <div class="table-header">
                            <h3>Tüm Ödeme Yöntemleri</h3>
                        </div>
                        
                        <div class="data-table">
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Kripto Para</th>
                                        <th>Cüzdan Adresi</th>
                                        <th>Durum</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payment_methods as $method): ?>
                                    <tr>
                                        <td><?php echo $method['id']; ?></td>
                                        <td>
                                            <strong><?php echo $method['coin_name']; ?></strong>
                                        </td>
                                        <td>
                                            <code class="wallet-address"><?php echo $method['wallet_address']; ?></code>
                                            <button class="btn btn-sm btn-secondary copy-btn" 
                                                    data-address="<?php echo $method['wallet_address']; ?>">
                                                Kopyala
                                            </button>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $method['is_active'] ? 'badge-success' : 'badge-secondary'; ?>">
                                                <?php echo $method['is_active'] ? 'Aktif' : 'Pasif'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="?action=edit&id=<?php echo $method['id']; ?>" class="btn btn-sm btn-primary">Düzenle</a>
                                                <a href="?delete=<?php echo $method['id']; ?>" class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Bu ödeme yöntemini silmek istediğinizden emin misiniz?')">Sil</a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="../assets/js/admin.js"></script>
    <script>
    // Cüzdan adresi kopyalama
    document.querySelectorAll('.copy-btn').forEach(button => {
        button.addEventListener('click', function() {
            const address = this.getAttribute('data-address');
            navigator.clipboard.writeText(address).then(() => {
                const originalText = this.textContent;
                this.textContent = 'Kopyalandı!';
                setTimeout(() => {
                    this.textContent = originalText;
                }, 2000);
            });
        });
    });
    </script>
</body>
</html>