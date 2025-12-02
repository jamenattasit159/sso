<?php
require 'config.php';

// ดึงข้อมูลแบนเนอร์
$banner = $pdo->query("SELECT * FROM banners WHERE status='active' ORDER BY created_at DESC LIMIT 1")->fetch();

// ดึงข้อมูลผู้บริหาร
$directors = $pdo->query("SELECT * FROM directors WHERE status='active' ORDER BY created_at DESC")->fetchAll();

// ดึงข้อมูลประกาศ
$announcements = $pdo->query("SELECT * FROM announcements WHERE status='active' ORDER BY created_at DESC LIMIT 6")->fetchAll();

// ดึงข้อมูลหน่วยงาน
$orgInfo = $pdo->query("SELECT * FROM organization_info LIMIT 1")->fetch();
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $orgInfo['name'] ?? 'สถาบันอุตสาหกรรมสุขภาพ'; ?></title>
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
            line-height: 1.6;
            color: var(--text);
            background: var(--light);
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

        .navbar-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .navbar h1 {
            font-size: 24px;
            margin: 0;
        }

        .nav-menu {
            list-style: none;
            display: flex;
            gap: 25px;
            flex-wrap: wrap;
        }

        .nav-menu a {
            color: white;
            text-decoration: none;
            transition: opacity 0.3s;
            font-weight: 500;
        }

        .nav-menu a:hover {
            opacity: 0.8;
        }

        .hamburger {
            display: none;
            flex-direction: column;
            gap: 5px;
            cursor: pointer;
        }

        .hamburger span {
            width: 25px;
            height: 3px;
            background: white;
            border-radius: 2px;
        }

        /* Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Banner */
        .banner-section {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            padding: 40px 20px;
            text-align: center;
            min-height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .banner-image {
            max-width: 100%;
            max-height: 400px;
            height: auto;
            border-radius: 8px;
            object-fit: cover;
        }

        /* Section */
        .section {
            padding: 60px 20px;
            background: white;
        }

        .section:nth-child(even) {
            background: var(--light);
        }

        .section h2 {
            text-align: center;
            font-size: 32px;
            margin-bottom: 40px;
            color: #2c3e50;
            position: relative;
            padding-bottom: 15px;
        }

        .section h2:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 4px;
            background: var(--primary);
            border-radius: 2px;
        }

        /* Directors Grid */
        .directors-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }

        .director-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            text-align: center;
        }

        .director-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .director-card img {
            width: 100%;
            height: 280px;
            object-fit: cover;
        }

        .director-info {
            padding: 20px;
        }

        .director-info h3 {
            font-size: 18px;
            margin-bottom: 8px;
            color: #2c3e50;
        }

        .director-info p {
            color: var(--primary);
            font-weight: bold;
            margin-bottom: 10px;
            font-size: 13px;
        }

        .director-info .description {
            font-size: 13px;
            color: #666;
            line-height: 1.5;
        }

        /* Announcements Grid */
        .announcements-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }

        .announce-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            display: flex;
            flex-direction: column;
        }

        .announce-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        /* Header dengan warna oranye */
        .announce-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            padding: 20px;
            color: white;
            min-height: 120px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .announce-date {
            color: white;
            font-size: 12px;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .announce-header h3 {
            margin: 10px 0 0 0;
            color: white;
            font-size: 16px;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .announce-content {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .announce-content p {
            color: #666;
            font-size: 13px;
            line-height: 1.6;
            margin-bottom: 15px;
            flex-grow: 1;
        }

        .announce-meta {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .file-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #fff3cd;
            color: #856404;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            text-decoration: none;
            transition: all 0.3s;
        }

        .file-badge:hover {
            background: #ffe69c;
            color: #664d03;
        }

        .file-badge i {
            font-size: 12px;
        }

        .read-more {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--primary);
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            transition: background 0.3s;
            font-weight: 500;
            font-size: 13px;
            width: fit-content;
        }

        .read-more:hover {
            background: var(--secondary);
        }

        /* Footer */
        footer {
            background: #2c3e50;
            color: white;
            padding: 40px 20px 20px;
            margin-top: 60px;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }

        .footer-section h3 {
            margin-bottom: 15px;
            color: var(--primary);
        }

        .footer-section ul {
            list-style: none;
        }

        .footer-section ul li {
            margin-bottom: 8px;
        }

        .footer-section a {
            color: #ecf0f1;
            text-decoration: none;
            transition: color 0.3s;
            font-size: 13px;
        }

        .footer-section a:hover {
            color: var(--primary);
        }

        .footer-bottom {
            max-width: 1200px;
            margin: 0 auto;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 20px;
            text-align: center;
            color: #bdc3c7;
            font-size: 13px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hamburger {
                display: flex;
            }

            .navbar h1 {
                font-size: 20px;
            }

            .nav-menu {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: var(--secondary);
                flex-direction: column;
                gap: 0;
                padding: 15px;
                width: 100%;
            }

            .nav-menu.active {
                display: flex;
            }

            .nav-menu a {
                padding: 10px 0;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            }

            .section h2 {
                font-size: 24px;
            }

            .section {
                padding: 40px 20px;
            }

            .directors-grid,
            .announcements-grid {
                grid-template-columns: 1fr;
            }

            .banner-section {
                padding: 30px 20px;
                min-height: 200px;
            }

            .footer-content {
                grid-template-columns: 1fr;
                gap: 20px;
            }
        }

        @media (max-width: 480px) {
            .navbar-container {
                gap: 10px;
            }

            .navbar h1 {
                font-size: 18px;
            }

            .container {
                padding: 0 15px;
            }

            .section {
                padding: 30px 15px;
            }

            .section h2 {
                font-size: 20px;
                margin-bottom: 25px;
            }

            .director-card img {
                height: 200px;
            }

            .announce-meta {
                flex-direction: column;
                align-items: flex-start;
            }

            .file-badge {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="navbar-container">
            <h1><?php echo htmlspecialchars($orgInfo['logo'] ?? '🏥'); ?>
                <?php echo sanitize($orgInfo['name'] ?? 'สถาบันอุตสาหกรรมสุขภาพ'); ?></h1>
            <ul class="nav-menu">
                <li><a href="#home">หน้าแรก</a></li>
                <li><a href="#directors">ผู้บริหาร</a></li>
                <li><a href="#announcements">ประกาศ</a></li>
                <li><a href="admin/login.php">จัดการเว็บ</a></li>
            </ul>
            <div class="hamburger" onclick="toggleMenu()">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>

    <!-- Banner -->
    <section class="banner-section" id="home">
        <?php if ($banner): ?>
            <img src="uploads/banners/<?php echo sanitize($banner['image']); ?>" alt="Banner" class="banner-image">
        <?php else: ?>
            <div style="color: white; font-size: 20px; text-align: center;">
                <i class="fas fa-image"></i> ยังไม่มีแบนเนอร์
            </div>
        <?php endif; ?>
    </section>

    <!-- Directors Section -->
    <section class="section" id="directors">
        <div class="container">
            <h2>👔 ผู้บริหาร</h2>
            <div class="directors-grid">
                <?php if (!empty($directors)): ?>
                    <?php foreach ($directors as $director): ?>
                        <div class="director-card">
                            <?php if ($director['image']): ?>
                                <img src="uploads/directors/<?php echo sanitize($director['image']); ?>"
                                    alt="<?php echo sanitize($director['name']); ?>">
                            <?php else: ?>
                                <div
                                    style="width: 100%; height: 280px; background: #ddd; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-user" style="font-size: 48px; color: #999;"></i>
                                </div>
                            <?php endif; ?>
                            <div class="director-info">
                                <h3><?php echo sanitize($director['name']); ?></h3>
                                <p><?php echo sanitize($director['position']); ?></p>
                                <?php if ($director['description']): ?>
                                    <div class="description"><?php echo sanitize($director['description']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="text-align: center; color: #999; grid-column: 1/-1; padding: 40px;">
                        <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 15px; display: block;"></i>
                        <p>ยังไม่มีข้อมูลผู้บริหาร</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Announcements Section -->
    <section class="section" id="announcements">
        <div class="container">
            <h2>📢 ประกาศและข่าวสาร</h2>
            <div class="announcements-grid">
                <?php if (!empty($announcements)): ?>
                    <?php foreach ($announcements as $announce): ?>
                        <div class="announce-card">
                            <!-- Header สีส้ม -->
                            <div class="announce-header">
                                <div class="announce-date">
                                    <i class="fas fa-calendar"></i>
                                    <?php echo date('d/m/Y', strtotime($announce['created_at'])); ?>
                                </div>
                                <h3><?php echo sanitize($announce['title']); ?></h3>
                            </div>

                            <!-- Body -->
                            <div class="announce-content">
                                <p><?php echo sanitize(substr(strip_tags($announce['content']), 0, 100)); ?>...</p>

                                <!-- แสดงไฟล์แนบ -->
                                <?php if ($announce['image']): ?>
                                    <div class="announce-meta">
                                        <a href="uploads/files/<?php echo htmlspecialchars($announce['image']); ?>" download
                                            class="file-badge">
                                            <i class="fas fa-download"></i>
                                            ดาวน์โหลด <?php echo strtoupper(pathinfo($announce['image'], PATHINFO_EXTENSION)); ?>
                                        </a>
                                    </div>
                                <?php endif; ?>

                                <a href="announcement.php?id=<?php echo $announce['id']; ?>" class="read-more">
                                    <span>อ่านเพิ่มเติม</span>
                                    <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="text-align: center; color: #999; grid-column: 1/-1; padding: 40px;">
                        <i class="fas fa-newspaper" style="font-size: 48px; margin-bottom: 15px; display: block;"></i>
                        <p>ยังไม่มีประกาศ</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>เกี่ยวกับเรา</h3>
                <p><?php echo sanitize($orgInfo['description'] ?? ''); ?></p>
            </div>

            <div class="footer-section">
                <h3>📞 ติดต่อเรา</h3>
                <ul>
                    <?php if ($orgInfo && $orgInfo['phone']): ?>
                        <li><a href="tel:<?php echo htmlspecialchars($orgInfo['phone']); ?>">
                                📱 <?php echo sanitize($orgInfo['phone']); ?>
                            </a></li>
                    <?php endif; ?>
                    <?php if ($orgInfo && $orgInfo['email']): ?>
                        <li><a href="mailto:<?php echo htmlspecialchars($orgInfo['email']); ?>">
                                ✉️ <?php echo sanitize($orgInfo['email']); ?>
                            </a></li>
                    <?php endif; ?>
                    <?php if ($orgInfo && $orgInfo['address']): ?>
                        <li>📍 <?php echo sanitize($orgInfo['address']); ?></li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="footer-section">
                <h3>เมนูด่วน</h3>
                <ul>
                    <li><a href="#home">หน้าแรก</a></li>
                    <li><a href="#directors">ผู้บริหาร</a></li>
                    <li><a href="#announcements">ประกาศ</a></li>
                    <li><a href="admin/login.php">ระบบแอดมิน</a></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; 2025 <?php echo htmlspecialchars($orgInfo['logo'] ?? '🏥'); ?>
                <?php echo sanitize($orgInfo['name'] ?? 'สถาบันอุตสาหกรรมสุขภาพ'); ?>. All rights reserved.</p>
        </div>
    </footer>

    <script>
        function toggleMenu() {
            const menu = document.querySelector('.nav-menu');
            menu.classList.toggle('active');
        }

        // ปิดเมนูเมื่อคลิกลิงก์
        document.querySelectorAll('.nav-menu a').forEach(link => {
            link.addEventListener('click', () => {
                document.querySelector('.nav-menu').classList.remove('active');
            });
        });
    </script>
</body>

</html>