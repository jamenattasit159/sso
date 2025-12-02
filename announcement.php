<?php
require 'config.php';

// ดึง ID จาก URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (empty($id)) {
    header('Location: index.php');
    exit;
}

// ดึงข้อมูลประกาศ
try {
    $stmt = $pdo->prepare("SELECT * FROM announcements WHERE id = ? AND status = 'active'");
    $stmt->execute([$id]);
    $announce = $stmt->fetch();

    if (!$announce) {
        header('Location: index.php');
        exit;
    }
} catch (Exception $e) {
    header('Location: index.php');
    exit;
}

// ดึงข้อมูลหน่วยงาน
$orgInfo = $pdo->query("SELECT * FROM organization_info LIMIT 1")->fetch();

// ดึงประกาศที่เกี่ยวข้อง
$related = $pdo->query(
    "SELECT * FROM announcements WHERE status='active' AND id != $id ORDER BY created_at DESC LIMIT 3"
)->fetchAll();
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($announce['title']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #f97316;
            --secondary: #ea580c;
            --text: #333;
            --light: #f5f7fa;
            --border: #ecf0f1;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text);
            background: var(--light);
            line-height: 1.6;
        }

        /* Navbar */
        .navbar {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 15px 0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .navbar h1 {
            font-size: 22px;
            margin: 0;
        }

        .back-link {
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 5px;
            transition: all 0.3s;
        }

        .back-link:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        /* Main Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Content Layout */
        .content-wrapper {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            padding: 40px 0;
        }

        /* Main Article */
        .article {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .article h1 {
            font-size: 28px;
            margin-bottom: 15px;
            color: #2c3e50;
            line-height: 1.4;
        }

        .article-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--border);
            flex-wrap: wrap;
            font-size: 14px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
        }

        .meta-item i {
            color: var(--primary);
            font-size: 16px;
        }

        .article-content {
            color: #555;
            font-size: 15px;
            line-height: 1.8;
        }

        .article-content p {
            margin-bottom: 15px;
        }

        .article-content img {
            max-width: 100%;
            height: auto;
            margin: 20px 0;
            border-radius: 8px;
        }

        /* Sidebar */
        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .sidebar-box {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .sidebar-box h3 {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 3px solid var(--primary);
            color: #2c3e50;
            font-size: 16px;
        }

        .contact-info p {
            margin-bottom: 12px;
            font-size: 13px;
            line-height: 1.6;
        }

        .contact-info strong {
            color: #2c3e50;
            display: block;
            margin-bottom: 5px;
        }

        .contact-info a {
            color: var(--primary);
            text-decoration: none;
        }

        .contact-info a:hover {
            text-decoration: underline;
        }

        .related-item {
            padding: 12px 0;
            border-bottom: 1px solid var(--border);
        }

        .related-item:last-child {
            border-bottom: none;
        }

        .related-link {
            color: var(--primary);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            display: block;
            line-height: 1.5;
            transition: color 0.3s;
        }

        .related-link:hover {
            color: var(--secondary);
        }

        .related-date {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }

        /* Footer */
        footer {
            background: #2c3e50;
            color: white;
            padding: 40px 0 20px;
            margin-top: 60px;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }

        .footer-section h4 {
            color: var(--primary);
            margin-bottom: 15px;
        }

        .footer-section p,
        .footer-section a {
            font-size: 13px;
            color: #ecf0f1;
            line-height: 1.8;
        }

        .footer-section a {
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-section a:hover {
            color: var(--primary);
        }

        .footer-bottom {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            text-align: center;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: #bdc3c7;
            font-size: 13px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .navbar-content {
                flex-direction: column;
                align-items: flex-start;
            }

            .navbar h1 {
                font-size: 18px;
            }

            .back-link {
                width: 100%;
                justify-content: center;
            }

            .content-wrapper {
                grid-template-columns: 1fr;
                gap: 20px;
                padding: 20px 0;
            }

            .article {
                padding: 20px;
            }

            .article h1 {
                font-size: 22px;
            }

            .article-meta {
                flex-direction: column;
                gap: 10px;
            }

            .article-content {
                font-size: 14px;
            }

            .footer-content {
                grid-template-columns: 1fr;
                gap: 20px;
            }
        }

        @media (max-width: 480px) {
            .navbar {
                padding: 10px 0;
            }

            .navbar-content {
                padding: 0 15px;
            }

            .container {
                padding: 0 15px;
            }

            .article {
                padding: 15px;
                border-radius: 8px;
            }

            .article h1 {
                font-size: 18px;
            }

            .sidebar-box {
                padding: 15px;
            }

            .article-meta {
                font-size: 12px;
            }
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="navbar-content">
            <h1>🏥 <?php echo sanitize($orgInfo['name'] ?? 'สถาบัน'); ?></h1>
            <a href="index.php" class="back-link">
                <i class="fas fa-arrow-left"></i>
                <span>กลับหน้าแรก</span>
            </a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <div class="content-wrapper">
            <!-- Article -->
            <article class="article">
                <h1><?php echo sanitize($announce['title']); ?></h1>

                <div class="article-meta">
                    <div class="meta-item">
                        <i class="fas fa-calendar"></i>
                        <span><?php echo date('d/m/Y H:i', strtotime($announce['created_at'])); ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-user"></i>
                        <span>ประกาศจากหน่วยงาน</span>
                    </div>
                </div>

                <div class="article-content">
                    <?php echo nl2br(sanitize($announce['content'])); ?>
                </div>
            </article>

            <!-- Sidebar -->
            <aside class="sidebar">
                <!-- Contact Box -->
                <div class="sidebar-box">
                    <h3><i class="fas fa-phone" style="margin-right: 8px;"></i>ติดต่อเรา</h3>
                    <div class="contact-info">
                        <?php if ($orgInfo && $orgInfo['phone']): ?>
                            <div>
                                <strong>📞 โทรศัพท์:</strong>
                                <a href="tel:<?php echo htmlspecialchars($orgInfo['phone']); ?>">
                                    <?php echo sanitize($orgInfo['phone']); ?>
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php if ($orgInfo && $orgInfo['email']): ?>
                            <div>
                                <strong>✉️ อีเมล:</strong>
                                <a href="mailto:<?php echo htmlspecialchars($orgInfo['email']); ?>">
                                    <?php echo sanitize($orgInfo['email']); ?>
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php if ($orgInfo && $orgInfo['address']): ?>
                            <div>
                                <strong>📍 ที่อยู่:</strong>
                                <p><?php echo sanitize($orgInfo['address']); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Related Announcements -->
                <div class="sidebar-box">
                    <h3><i class="fas fa-newspaper" style="margin-right: 8px;"></i>ประกาศอื่นๆ</h3>
                    <?php if (!empty($related)): ?>
                        <?php foreach ($related as $rel): ?>
                            <div class="related-item">
                                <a href="announcement.php?id=<?php echo $rel['id']; ?>" class="related-link">
                                    <?php echo sanitize(substr($rel['title'], 0, 50)); ?>...
                                </a>
                                <div class="related-date">
                                    <?php echo date('d/m/Y', strtotime($rel['created_at'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: #999; font-size: 13px;">ไม่มีประกาศอื่น</p>
                    <?php endif; ?>
                </div>
            </aside>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h4>เกี่ยวกับเรา</h4>
                <p><?php echo sanitize($orgInfo['description'] ?? ''); ?></p>
            </div>

            <div class="footer-section">
                <h4>เมนูหลัก</h4>
                <a href="index.php">หน้าแรก</a><br>
                <a href="index.php#directors">ผู้บริหาร</a><br>
                <a href="index.php#announcements">ประกาศ</a>
            </div>

            <div class="footer-section">
                <h4>เข้าถึง</h4>
                <a href="admin/login.php">ระบบแอดมิน</a><br>
                <a href="index.php">กลับหน้าแรก</a>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; 2025 <?php echo sanitize($orgInfo['name'] ?? 'สถาบันอุตสาหกรรมสุขภาพ'); ?>. All rights reserved.
            </p>
        </div>
    </footer>

</body>

</html>