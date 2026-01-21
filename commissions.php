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

$page_title = "Komisyon Yönetimi";
$admin_page = true;

// Komisyon ayarlarını kaydetme
if ($_POST && isset($_POST['save_commission_settings'])) {
    $success_count = 0;
    
    // Her GPU için komisyon tutarını işle
    foreach ($_POST['weekly_commissions'] as $gpu_id => $weekly_commission) {
        $gpu_id = intval($gpu_id);
        $weekly_commission = floatval($weekly_commission);
        
        if ($weekly_commission >= 0) {
            // YENİ HESAPLAMA: Haftalık komisyon × 4 = Aylık gelir
            $new_monthly_income = $weekly_commission * 4;
            
            // Yüzdelik getiriyi hesapla ve commission_rate'i güncelle
            $gpu_stmt = $pdo->prepare("SELECT price FROM gpus WHERE id = ?");
            $gpu_stmt->execute([$gpu_id]);
            $gpu = $gpu_stmt->fetch();
            
            if ($gpu && $gpu['price'] > 0) {
                $new_commission_rate = ($new_monthly_income / $gpu['price']) * 100;
                
                // Hem monthly_income hem de commission_rate'i güncelle
                $update_stmt = $pdo->prepare("UPDATE gpus SET monthly_income = ?, commission_rate = ? WHERE id = ?");
                if ($update_stmt->execute([round($new_monthly_income, 2), round($new_commission_rate, 2), $gpu_id])) {
                    $success_count++;
                }
            }
        }
    }
    
    if ($success_count > 0) {
        $success = "{$success_count} GPU'nun gelirleri ve komisyon oranları başarıyla güncellendi!";
    } else {
        $error = "Güncelleme yapılamadı!";
    }
    
    // Sayfayı yenile
    header("Location: commissions.php");
    exit;
}

// DOĞRU KOMİSYON HESAPLAMALARI

// Toplam yatırım (sermaye) - tüm satın alınan GPU'ların toplam fiyatı
$total_investment = $pdo->query("
    SELECT SUM(g.price) as total_investment 
    FROM user_gpus ug 
    JOIN gpus g ON ug.gpu_id = g.id
")->fetchColumn() ?: 0;

// Toplam aylık gelir
$total_monthly_income = $pdo->query("
    SELECT SUM(g.monthly_income) as total_income 
    FROM user_gpus ug 
    JOIN gpus g ON ug.gpu_id = g.id
")->fetchColumn() ?: 0;

// Yüzdelik komisyon hesaplama - DOĞRU FORMÜL
$percentage_commission = 0;
if ($total_investment > 0) {
    $percentage_commission = ($total_monthly_income / $total_investment) * 100;
}

// Zaman bazlı komisyon hesaplamaları
$daily_commission = ($total_monthly_income / 30);
$weekly_commission = ($total_monthly_income / 4);
$monthly_commission = $total_monthly_income;

// Aktif GPU sayısı
$active_gpus = $pdo->query("SELECT COUNT(*) FROM user_gpus")->fetchColumn();

// GPU bazlı detaylı komisyon raporu - DOĞRU HESAPLAMA
$gpu_commissions = $pdo->query("
    SELECT 
        g.id,
        g.name,
        g.monthly_income,
        g.commission_rate,
        g.price,
        COUNT(ug.id) as gpu_count,
        SUM(g.price) as total_investment,
        SUM(g.monthly_income) as total_monthly_income,
        g.commission_rate as percentage_commission, -- Doğrudan veritabanındaki commission_rate kullan
        SUM(g.monthly_income) as monthly_commission,
        SUM(g.monthly_income / 4) as weekly_commission,
        SUM(g.monthly_income / 30) as daily_commission
    FROM gpus g 
    LEFT JOIN user_gpus ug ON g.id = ug.gpu_id 
    GROUP BY g.id, g.name, g.monthly_income, g.commission_rate, g.price
    ORDER BY monthly_commission DESC
")->fetchAll();

// Tüm aktif GPU'ları getir (komisyon oranları için)
$all_gpus = $pdo->query("SELECT * FROM gpus WHERE is_active = 1 ORDER BY monthly_income DESC")->fetchAll();

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
        .commission-breakdown {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        .breakdown-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .breakdown-item:last-child {
            border-bottom: none;
        }
        .gpu-commission-form {
            background: #e7f3ff;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #3498db;
        }
        .gpu-commission-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #ddd;
            background: white;
            margin-bottom: 10px;
            border-radius: 8px;
        }
        .gpu-commission-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        .gpu-info {
            flex: 1;
        }
        .gpu-commission-input {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .commission-input {
            width: 120px;
            padding: 8px 12px;
            border: 2px solid #ddd;
            border-radius: 6px;
            text-align: right;
        }
        .commission-input:focus {
            border-color: #3498db;
            outline: none;
        }
        .commission-rate-badge {
            background: #3498db;
            color: white;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.9rem;
            font-weight: bold;
            min-width: 80px;
            text-align: center;
        }
        .monthly-income-badge {
            background: #27ae60;
            color: white;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.9rem;
            font-weight: bold;
            min-width: 120px;
            text-align: center;
        }
        .percentage-badge {
            background: #e74c3c;
            color: white;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.9rem;
            font-weight: bold;
            min-width: 100px;
            text-align: center;
        }
        .gpu-stats {
            font-size: 0.85rem;
            color: #666;
            margin-top: 5px;
        }
        .form-actions {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #3498db;
        }
        .total-weekly-commission {
            background: #2ecc71;
            color: white;
            padding: 10px 15px;
            border-radius: 8px;
            font-weight: bold;
            margin-top: 15px;
            text-align: center;
        }
        .income-preview {
            background: #fff3cd;
            padding: 10px;
            border-radius: 6px;
            margin-top: 10px;
            border-left: 4px solid #ffc107;
        }
        .calculation-info {
            background: #d1ecf1;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #17a2b8;
        }
        .stats-grid.admin .stat-card.admin .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
        }
        .commission-preview {
            background: #e8f5e8;
            padding: 8px 12px;
            border-radius: 6px;
            margin-top: 5px;
            font-size: 0.85rem;
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
                <a href="commissions.php" class="nav-item active">
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
                <h1>Komisyon Yönetimi</h1>
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

                <!-- Hesaplama Bilgisi -->
                <div class="calculation-info">
                    <h4>📊 DOĞRU KOMİSYON HESAPLAMA SİSTEMİ</h4>
                    <p><strong>Formül:</strong> Getiri Oranı = (Aylık Gelir / GPU Fiyatı) × 100</p>
                    <p><strong>Örnekler:</strong></p>
                    <ul>
                        <li>500$ GPU → 300$ Aylık Gelir = (300/500)×100 = <strong>%60 Getiri</strong></li>
                        <li>1000$ GPU → 600$ Aylık Gelir = (600/1000)×100 = <strong>%60 Getiri</strong></li>
                        <li>1500$ GPU → 900$ Aylık Gelir = (900/1500)×100 = <strong>%60 Getiri</strong></li>
                    </ul>
                </div>

                <!-- Komisyon İstatistikleri -->
                <div class="stats-grid admin">
                    <div class="stat-card admin">
                        <div class="stat-icon">💰</div>
                        <div class="stat-info">
                            <h3>Toplam Yatırım</h3>
                            <p class="stat-value"><?php echo formatPrice($total_investment); ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-card admin">
                        <div class="stat-icon">📈</div>
                        <div class="stat-info">
                            <h3>Ortalama Getiri</h3>
                            <p class="stat-value">%<?php echo number_format($percentage_commission, 2); ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-card admin">
                        <div class="stat-icon">🖥️</div>
                        <div class="stat-info">
                            <h3>Aktif GPU'lar</h3>
                            <p class="stat-value"><?php echo $active_gpus; ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-card admin">
                        <div class="stat-icon">📊</div>
                        <div class="stat-info">
                            <h3>Aylık Gelir</h3>
                            <p class="stat-value"><?php echo formatPrice($monthly_commission); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Zaman Bazlı Gelirler -->
                <div class="stats-grid admin">
                    <div class="stat-card admin">
                        <div class="stat-icon">🌞</div>
                        <div class="stat-info">
                            <h3>Günlük Gelir</h3>
                            <p class="stat-value"><?php echo formatPrice($daily_commission); ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-card admin">
                        <div class="stat-icon">📅</div>
                        <div class="stat-info">
                            <h3>Haftalık Gelir</h3>
                            <p class="stat-value"><?php echo formatPrice($weekly_commission); ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-card admin">
                        <div class="stat-icon">💰</div>
                        <div class="stat-info">
                            <h3>Aylık Gelir</h3>
                            <p class="stat-value"><?php echo formatPrice($monthly_commission); ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-card admin">
                        <div class="stat-icon">🎯</div>
                        <div class="stat-info">
                            <h3>Getiri Oranı</h3>
                            <p class="stat-value">%<?php echo number_format($percentage_commission, 2); ?></p>
                        </div>
                    </div>
                </div>

                <div class="content-row">
                    <div class="content-col">
                        <!-- Gelir Dağılımı -->
                        <div class="content-card">
                            <h3>💰 Gelir Dağılımı</h3>
                            <div class="commission-breakdown">
                                <div class="breakdown-item">
                                    <span>Toplam Yatırım:</span>
                                    <strong><?php echo formatPrice($total_investment); ?></strong>
                                </div>
                                <div class="breakdown-item">
                                    <span>Toplam Aylık Gelir:</span>
                                    <strong><?php echo formatPrice($total_monthly_income); ?></strong>
                                </div>
                                <div class="breakdown-item">
                                    <span>Ortalama Getiri:</span>
                                    <strong class="percentage-badge">%<?php echo number_format($percentage_commission, 2); ?></strong>
                                </div>
                                <div class="breakdown-item">
                                    <span>Aylık Getiri/Oran:</span>
                                    <strong><?php echo formatPrice($total_monthly_income); ?> / %<?php echo number_format($percentage_commission, 2); ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="content-col">
                        <!-- GPU Bazlı Komisyonlar -->
                        <div class="content-card">
                            <h3>🖥️ GPU Bazlı Getiri Dağılımı</h3>
                            <div class="data-table">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>GPU Adı</th>
                                            <th>Adet</th>
                                            <th>Yatırım</th>
                                            <th>Aylık Gelir</th>
                                            <th>Getiri %</th>
                                            <th>Günlük</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($gpu_commissions as $commission): ?>
                                        <tr>
                                            <td><?php echo $commission['name']; ?></td>
                                            <td><?php echo $commission['gpu_count']; ?> adet</td>
                                            <td><?php echo formatPrice($commission['total_investment'] ?: 0); ?></td>
                                            <td><?php echo formatPrice($commission['total_monthly_income'] ?: 0); ?></td>
                                            <td>
                                                <span class="percentage-badge">
                                                    %<?php echo number_format($commission['percentage_commission'] ?: 0, 2); ?>
                                                </span>
                                            </td>
                                            <td><?php echo formatPrice($commission['daily_commission'] ?: 0); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- GPU Bazlı Komisyon Ayarları -->
                <div class="gpu-commission-form">
                    <h3>🎯 GPU Bazlı Haftalık Komisyon ve Getiri Oranı Ayarları</h3>
                    <p class="text-muted">Her GPU için haftalık gelir girin. Sistem otomatik olarak aylık geliri ve getiri oranını hesaplayacaktır.</p>
                    
                    <form method="POST">
                        <div class="gpu-commission-list">
                            <?php 
                            $total_weekly_input = 0;
                            $total_monthly_income = 0;
                            foreach ($all_gpus as $gpu): 
                                // Mevcut haftalık komisyonu hesapla (ters formül)
                                $current_weekly_commission = $gpu['monthly_income'] / 4;
                                $total_weekly_input += $current_weekly_commission;
                                $total_monthly_income += $gpu['monthly_income'];
                                
                                // Mevcut getiri oranını hesapla
                                $current_return_rate = ($gpu['monthly_income'] / $gpu['price']) * 100;
                            ?>
                            <div class="gpu-commission-item" data-gpu-id="<?php echo $gpu['id']; ?>">
                                <div class="gpu-info">
                                    <strong><?php echo $gpu['name']; ?></strong>
                                    <div class="gpu-stats">
                                        Fiyat: <?php echo formatPrice($gpu['price']); ?> | 
                                        Mevcut Getiri: %<?php echo number_format($current_return_rate, 2); ?>
                                    </div>
                                    <div class="income-preview" id="income-preview-<?php echo $gpu['id']; ?>">
                                        <strong>Yeni Aylık Gelir:</strong> <span id="new-income-<?php echo $gpu['id']; ?>">$0.00</span>
                                        <div class="commission-preview">
                                            <strong>Yeni Getiri Oranı:</strong> <span id="new-rate-<?php echo $gpu['id']; ?>">%0.00</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="gpu-commission-input">
                                    <span><strong>Haftalık:</strong> $</span>
                                    <input type="number" 
                                           name="weekly_commissions[<?php echo $gpu['id']; ?>]" 
                                           class="commission-input weekly-commission-input"
                                           value="<?php echo number_format($current_weekly_commission, 2, '.', ''); ?>"
                                           step="0.01" 
                                           min="0" 
                                           placeholder="0.00"
                                           data-gpu-id="<?php echo $gpu['id']; ?>"
                                           data-gpu-price="<?php echo $gpu['price']; ?>">
                                    <div class="monthly-income-badge" id="current-income-<?php echo $gpu['id']; ?>">
                                        <?php echo formatPrice($gpu['monthly_income']); ?>
                                    </div>
                                    <div class="commission-rate-badge" id="current-rate-<?php echo $gpu['id']; ?>">
                                        %<?php echo number_format($current_return_rate, 2); ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Toplam Bilgiler -->
                        <div class="total-weekly-commission">
                            📊 Toplam Haftalık Gelir: $<span id="total-weekly"><?php echo number_format($total_weekly_input, 2); ?></span>
                        </div>
                        <div class="total-weekly-commission" style="background: #e74c3c; margin-top: 5px;">
                            📈 Toplam Aylık Gelir: $<span id="total-monthly"><?php echo number_format($total_monthly_income, 2); ?></span>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="save_commission_settings" class="btn btn-primary btn-large">
                                💾 Tüm Gelirleri ve Oranları Güncelle
                            </button>
                            <small class="text-muted" style="display: block; margin-top: 10px;">
                                Not: Her GPU için girilen haftalık gelir × 4 = Aylık gelir ve (Aylık gelir / GPU fiyatı) × 100 = Getiri oranı olarak hesaplanacaktır.
                            </small>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/admin.js"></script>
    <script>
    // Gerçek zamanlı aylık gelir ve getiri oranı hesaplama
    document.addEventListener('DOMContentLoaded', function() {
        const commissionInputs = document.querySelectorAll('.weekly-commission-input');
        const totalWeeklyDisplay = document.getElementById('total-weekly');
        const totalMonthlyDisplay = document.getElementById('total-monthly');
        
        function updateCalculations() {
            let totalWeekly = 0;
            let totalMonthly = 0;
            
            commissionInputs.forEach(input => {
                const gpuId = input.getAttribute('data-gpu-id');
                const gpuPrice = parseFloat(input.getAttribute('data-gpu-price'));
                const weeklyValue = parseFloat(input.value) || 0;
                totalWeekly += weeklyValue;
                
                // Hesaplamalar: Haftalık × 4 = Aylık
                const monthlyIncome = weeklyValue * 4;
                totalMonthly += monthlyIncome;
                
                // Getiri oranı: (Aylık gelir / GPU fiyatı) × 100
                const returnRate = gpuPrice > 0 ? (monthlyIncome / gpuPrice) * 100 : 0;
                
                // Önizleme göster
                const preview = document.getElementById('income-preview-' + gpuId);
                const newIncomeSpan = document.getElementById('new-income-' + gpuId);
                const newRateSpan = document.getElementById('new-rate-' + gpuId);
                const currentIncomeBadge = document.getElementById('current-income-' + gpuId);
                const currentRateBadge = document.getElementById('current-rate-' + gpuId);
                
                if (weeklyValue > 0) {
                    newIncomeSpan.textContent = '$' + monthlyIncome.toFixed(2);
                    newRateSpan.textContent = '%' + returnRate.toFixed(2);
                    preview.style.display = 'block';
                    
                    // Mevcut değerleri güncelle (sadece görsel)
                    currentIncomeBadge.textContent = '$' + monthlyIncome.toFixed(2);
                    currentRateBadge.textContent = '%' + returnRate.toFixed(2);
                } else {
                    preview.style.display = 'none';
                    // Orijinal değerlere geri dön
                    const originalIncome = parseFloat('<?php echo $gpu["monthly_income"]; ?>') || 0;
                    const originalRate = parseFloat('<?php echo $current_return_rate; ?>') || 0;
                    currentIncomeBadge.textContent = '$' + originalIncome.toFixed(2);
                    currentRateBadge.textContent = '%' + originalRate.toFixed(2);
                }
            });
            
            // Toplamları güncelle
            totalWeeklyDisplay.textContent = totalWeekly.toFixed(2);
            totalMonthlyDisplay.textContent = totalMonthly.toFixed(2);
        }
        
        commissionInputs.forEach(input => {
            input.addEventListener('input', updateCalculations);
        });
        
        // İlk yüklemede hesaplamaları yap
        updateCalculations();
    });
    </script>
</body>
</html>