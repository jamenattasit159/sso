<?php
require 'config.php';

// ดึงข้อมูลบุคลากรทั้งหมด (ที่ไม่ใช่ ผอ.)
$personnels = $pdo->query("SELECT * FROM directors WHERE status='active' AND category='personnel' ORDER BY created_at ASC")->fetchAll();
$orgInfo = $pdo->query("SELECT * FROM organization_info LIMIT 1")->fetch();
$logoData = $orgInfo['logo'] ?? '🏥';
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>บุคลากร - <?php echo $orgInfo['name']; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* ยืม CSS หลักจากหน้า Index */
        :root { --primary: #f97316; --secondary: #ea580c; --text: #333; --light: #f5f7fa; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--light); color: var(--text); margin: 0; }
        
        .navbar { background: linear-gradient(135deg, var(--primary), var(--secondary)); padding: 15px 0; color: white; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .navbar-container { display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        .nav-menu { display: flex; gap: 20px; list-style: none; }
        .nav-menu a { color: white; text-decoration: none; }

        /* Grid สำหรับบุคลากร */
        .personnel-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }
        
        .person-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s;
            text-align: center;
        }
        .person-card:hover { transform: translateY(-5px); }
        .person-img {
            width: 100%;
            height: 250px;
            object-fit: cover;
            border-bottom: 3px solid var(--primary);
        }
        .person-info { padding: 15px; }
        .person-name { font-weight: bold; font-size: 1.1rem; color: #2c3e50; margin-bottom: 5px; }
        .person-pos { color: var(--primary); font-size: 0.9rem; }
        
        .page-header {
            text-align: center; margin: 40px 0;
        }
        .page-header h1 { color: #2c3e50; font-size: 2.5rem; margin-bottom: 10px; }
        .divider { width: 60px; height: 4px; background: var(--primary); margin: 0 auto; border-radius: 2px; }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="navbar-container">
            <h3><?php echo $orgInfo['name']; ?></h3>
            <ul class="nav-menu">
                <li><a href="index.php">หน้าแรก</a></li>
                <li><a href="personnel.php" style="font-weight: bold; text-decoration: underline;">บุคลากร</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1>คณะบุคลากร</h1>
            <div class="divider"></div>
            <p style="color: #666; margin-top: 10px;">บุคลากรของเราพร้อมให้บริการด้วยใจ</p>
        </div>

        <?php if (!empty($personnels)): ?>
            <div class="personnel-grid">
                <?php foreach ($personnels as $p): ?>
                    <div class="person-card">
                        <?php if ($p['image']): ?>
                            <img src="uploads/directors/<?php echo $p['image']; ?>" class="person-img" loading="lazy">
                        <?php else: ?>
                            <div style="height: 250px; background: #eee; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-user" style="font-size: 60px; color: #ccc;"></i>
                            </div>
                        <?php endif; ?>
                        <div class="person-info">
                            <div class="person-name"><?php echo htmlspecialchars($p['name']); ?></div>
                            <div class="person-pos"><?php echo htmlspecialchars($p['position']); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 50px; color: #999;">
                <i class="fas fa-user-friends" style="font-size: 50px; margin-bottom: 20px;"></i>
                <p>ยังไม่มีข้อมูลบุคลากร</p>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>