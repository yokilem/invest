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

$page_title = "Ekran Kartları Yönetimi";
$admin_page = true;

// GPU listesini getir
$gpus = $pdo->query("SELECT * FROM gpus ORDER BY created_at DESC")->fetchAll();

// GPU ekleme/düzenleme
if ($_POST && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action == 'add' || $action == 'edit') {
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $monthly_income = $_POST['monthly_income'];
        $commission_rate = $_POST['commission_rate'];
        $stock = $_POST['stock'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Resim yükleme
        $image_path = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_result = uploadFile($_FILES['image'], 'gpu_images');
            if ($upload_result['success']) {
                $image_path = $upload_result['file_path'];
            } else {
                $error = $upload_result['error'];
            }
        }
        
        if ($action == 'add') {
            $stmt = $pdo->prepare("INSERT INTO gpus (name, description, price, monthly_income, commission_rate, stock, is_active, image_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $description, $price, $monthly_income, $commission_rate, $stock, $is_active, $image_path]);
            $success = "GPU başarıyla eklendi!";
        } else {
            $id = $_POST['id'];
            
            // Eğer yeni resim yüklenmediyse, mevcut resmi koru
            if (!$image_path) {
                $stmt = $pdo->prepare("SELECT image_path FROM gpus WHERE id = ?");
                $stmt->execute([$id]);
                $current_gpu = $stmt->fetch();
                $image_path = $current_gpu['image_path'];
            }
            
            $stmt = $pdo->prepare("UPDATE gpus SET name=?, description=?, price=?, monthly_income=?, commission_rate=?, stock=?, is_active=?, image_path=? WHERE id=?");
            $stmt->execute([$name, $description, $price, $monthly_income, $commission_rate, $stock, $is_active, $image_path, $id]);
            $success = "GPU başarıyla güncellendi!";
        }
    }
}

// GPU silme
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // GPU'nun resmini de sil
    $stmt = $pdo->prepare("SELECT image_path FROM gpus WHERE id = ?");
    $stmt->execute([$id]);
    $gpu = $stmt->fetch();
    
    if ($gpu['image_path'] && file_exists('../' . $gpu['image_path'])) {
        unlink('../' . $gpu['image_path']);
    }
    
    $stmt = $pdo->prepare("DELETE FROM gpus WHERE id = ?");
    $stmt->execute([$id]);
    $success = "GPU başarıyla silindi!";
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
        .image-preview {
            max-width: 200px;
            max-height: 150px;
            border-radius: 8px;
            margin-top: 10px;
            display: none;
        }
        .current-image {
            max-width: 200px;
            max-height: 150px;
            border-radius: 8px;
            margin-top: 10px;
        }
        .upload-area {
            border: 2px dashed #ccc;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin-bottom: 15px;
            cursor: pointer;
            transition: border-color 0.3s;
        }
        .upload-area:hover {
            border-color: #3498db;
        }
        .upload-area.dragover {
            border-color: #3498db;
            background-color: #f8f9fa;
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
                <a href="gpus.php" class="nav-item active">
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
                <h1>Ekran Kartları Yönetimi</h1>
                <div class="admin-actions">
                    <a href="?action=add" class="btn btn-primary">Yeni GPU Ekle</a>
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

                <!-- GPU Ekleme/Düzenleme Formu -->
                <?php if (isset($_GET['action']) && ($_GET['action'] == 'add' || $_GET['action'] == 'edit')): ?>
                    <?php
                    $gpu = null;
                    if ($_GET['action'] == 'edit' && isset($_GET['id'])) {
                        $stmt = $pdo->prepare("SELECT * FROM gpus WHERE id = ?");
                        $stmt->execute([$_GET['id']]);
                        $gpu = $stmt->fetch();
                    }
                    ?>
                    <div class="content-card">
                        <h3><?php echo $_GET['action'] == 'add' ? 'Yeni GPU Ekle' : 'GPU Düzenle'; ?></h3>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="<?php echo $_GET['action']; ?>">
                            <?php if ($_GET['action'] == 'edit'): ?>
                                <input type="hidden" name="id" value="<?php echo $gpu['id']; ?>">
                            <?php endif; ?>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="name">GPU Adı:</label>
                                    <input type="text" id="name" name="name" required 
                                           value="<?php echo $gpu ? $gpu['name'] : ''; ?>">
                                </div>
                                <div class="form-group">
                                    <label for="price">Fiyat (₺):</label>
                                    <input type="number" id="price" name="price" required step="0.01"
                                           value="<?php echo $gpu ? $gpu['price'] : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="description">Açıklama:</label>
                                <textarea id="description" name="description" rows="3"><?php echo $gpu ? $gpu['description'] : ''; ?></textarea>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="monthly_income">Aylık Gelir (₺):</label>
                                    <input type="number" id="monthly_income" name="monthly_income" required step="0.01"
                                           value="<?php echo $gpu ? $gpu['monthly_income'] : ''; ?>">
                                </div>
                                <div class="form-group">
                                    <label for="commission_rate">Komisyon Oranı (%):</label>
                                    <input type="number" id="commission_rate" name="commission_rate" required step="0.01"
                                           value="<?php echo $gpu ? $gpu['commission_rate'] : '15'; ?>">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="stock">Stok:</label>
                                    <input type="number" id="stock" name="stock" required
                                           value="<?php echo $gpu ? $gpu['stock'] : '0'; ?>">
                                </div>
                                <div class="form-group">
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="is_active" value="1" 
                                               <?php echo ($gpu && $gpu['is_active']) || !$gpu ? 'checked' : ''; ?>>
                                        Aktif
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Resim Yükleme Alanı -->
                            <div class="form-group">
                                <label for="image">GPU Resmi:</label>
                                <div class="upload-area" id="uploadArea">
                                    <div id="uploadText">
                                        <p>📁 Resmi sürükleyip bırakın veya tıklayarak seçin</p>
                                        <small>JPEG, PNG veya GIF formatında, maksimum 5MB</small>
                                    </div>
                                    <input type="file" id="image" name="image" accept="image/*" style="display: none;" 
                                           onchange="previewImage(this)">
                                </div>
                                
                                <?php if ($gpu && $gpu['image_path']): ?>
                                    <div class="current-image-container">
                                        <p><strong>Mevcut Resim:</strong></p>
                                        <img src="../<?php echo $gpu['image_path']; ?>" alt="Mevcut GPU Resmi" class="current-image">
                                    </div>
                                <?php endif; ?>
                                
                                <img id="imagePreview" class="image-preview" alt="Resim önizleme">
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary"><?php echo $_GET['action'] == 'add' ? 'Ekle' : 'Güncelle'; ?></button>
                                <a href="gpus.php" class="btn btn-secondary">İptal</a>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <!-- GPU Listesi -->
                    <div class="content-card">
                        <div class="table-header">
                            <h3>Tüm Ekran Kartları</h3>
                            <div class="table-actions">
                                <input type="text" placeholder="GPU ara..." class="search-input">
                            </div>
                        </div>
                        
                        <div class="data-table">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Resim</th>
                                        <th>GPU Adı</th>
                                        <th>Fiyat</th>
                                        <th>Aylık Gelir</th>
                                        <th>Komisyon</th>
                                        <th>Stok</th>
                                        <th>Durum</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($gpus as $gpu): ?>
                                    <tr>
                                        <td>
                                            <?php if ($gpu['image_path']): ?>
                                                <img src="../<?php echo $gpu['image_path']; ?>" alt="<?php echo $gpu['name']; ?>" 
                                                     style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                            <?php else: ?>
                                                <div style="width: 50px; height: 50px; background: #f0f0f0; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #999;">
                                                    🖼️
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo $gpu['name']; ?></strong>
                                            <div class="text-muted"><?php echo $gpu['description']; ?></div>
                                        </td>
                                        <td><?php echo formatPrice($gpu['price']); ?></td>
                                        <td><?php echo formatPrice($gpu['monthly_income']); ?></td>
                                        <td>%<?php echo $gpu['commission_rate']; ?></td>
                                        <td><?php echo $gpu['stock']; ?> adet</td>
                                        <td>
                                            <span class="badge <?php echo $gpu['is_active'] ? 'badge-success' : 'badge-secondary'; ?>">
                                                <?php echo $gpu['is_active'] ? 'Aktif' : 'Pasif'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="?action=edit&id=<?php echo $gpu['id']; ?>" class="btn btn-sm btn-primary">Düzenle</a>
                                                <a href="?delete=<?php echo $gpu['id']; ?>" class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Bu GPU\'yu silmek istediğinizden emin misiniz?')">Sil</a>
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
    // Resim önizleme
    function previewImage(input) {
        const preview = document.getElementById('imagePreview');
        const uploadText = document.getElementById('uploadText');
        
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
                uploadText.innerHTML = '<p>✅ Resim seçildi</p>';
            }
            
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    // Sürükle bırak işlevselliği
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('image');
    
    uploadArea.addEventListener('click', () => {
        fileInput.click();
    });
    
    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.classList.add('dragover');
    });
    
    uploadArea.addEventListener('dragleave', () => {
        uploadArea.classList.remove('dragover');
    });
    
    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
        
        if (e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files;
            previewImage(fileInput);
        }
    });
    
    // Tablo arama işlevselliği
    document.querySelector('.search-input').addEventListener('input', function() {
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
    </script>
</body>
</html>