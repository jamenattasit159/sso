<?php
require 'config.php';

// 1. ดึงข้อมูลแบนเนอร์ (แก้จาก fetch เป็น fetchAll และเอา LIMIT 1 ออก)
$banners = $pdo->query("SELECT * FROM banners WHERE status='active' ORDER BY created_at DESC")->fetchAll();

// 2. ดึงข้อมูลผู้บริหาร (สำหรับฝั่งซ้าย)
$director = $pdo->query("SELECT * FROM directors WHERE status='active' AND category='director' LIMIT 1")->fetch();

// 3. ดึงข้อมูลรูปภาพประชาสัมพันธ์ (สำหรับฝั่งขวา) 
$pr_images = $pdo->query("SELECT * FROM files WHERE status='active' AND file_type IN ('jpg', 'jpeg', 'png', 'gif') ORDER BY created_at DESC LIMIT 5")->fetchAll();

// 4. ดึงข้อมูลประกาศ (สำหรับส่วนล่างสุด)
$announcements = $pdo->query("SELECT * FROM announcements WHERE status='active' ORDER BY created_at DESC LIMIT 4")->fetchAll();

// ดึงข้อมูลหน่วยงาน
$orgInfo = $pdo->query("SELECT * FROM organization_info LIMIT 1")->fetch();
$downloads = $pdo->query("SELECT * FROM announcements WHERE status='active' AND category='download' ORDER BY created_at DESC LIMIT 6")->fetchAll();
$procurements = $pdo->query("SELECT * FROM announcements WHERE status='active' AND category='procurement' ORDER BY created_at DESC LIMIT 6")->fetchAll();
$itas = $pdo->query("SELECT * FROM announcements WHERE status='active' AND category='ita' ORDER BY created_at DESC LIMIT 6")->fetchAll();

// --- ส่วนจัดการ LOGO และ FAVICON ---
$logoData = $orgInfo['logo'] ?? '🏥';
$isLogoFile = false;
$faviconHtml = '';

if (strpos($logoData, 'uploads/') !== false && file_exists($logoData)) {
    $isLogoFile = true;
    $ext = strtolower(pathinfo($logoData, PATHINFO_EXTENSION));
    $mime = match ($ext) {
        'ico' => 'image/x-icon',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'jpg', 'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        default => 'image/x-icon'
    };
    $faviconHtml = '<link rel="shortcut icon" href="' . $logoData . '" type="' . $mime . '">';
} else {
    $faviconHtml = '<link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>' . $logoData . '</text></svg>">';
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $orgInfo['name'] ?? 'สำนักงานสาธารณะสุขอ่างทอง'; ?></title>

    <?php echo $faviconHtml; ?>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* --- CSS Reset & Variables --- */
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

        /* --- Navbar --- */
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

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 24px;
            font-weight: bold;
            margin: 0;
            color: white;
            text-decoration: none;
        }

        .navbar-logo-img {
            height: 45px;
            width: auto;
            object-fit: contain;
            background: rgba(255, 255, 255, 0.1);
            padding: 2px;
            border-radius: 4px;
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

        /* --- Global Layout --- */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* --- Banner Section (แก้ไขใหม่) --- */
        .banner-section {
            background: #f97316;
            /* พื้นหลังสีส้ม */
            padding: 0;
            /* เอา padding ออกเพื่อให้รูปชิดขอบ */
            overflow: hidden;
            /* ซ่อนส่วนที่ล้น */
            width: 100%;
            position: relative;
        }

        .banner-slider-container {
            width: 100%;
            overflow: hidden;
            white-space: nowrap;
            /* บังคับให้รูปเรียงต่อกันแนวนอน */
        }

        .banner-track {
            display: inline-flex;
            /* Animation เลื่อนจากขวาไปซ้าย */
            animation: slideRightToLeft 30s linear infinite;
        }

        .banner-track:hover {
            animation-play-state: paused;
            /* เอาเมาส์ชี้แล้วหยุด */
        }

        .banner-slide-img {
            height: 450px;
            /* ความสูงของแบนเนอร์ */
            width: auto;
            /* กว้างตามสัดส่วนภาพ */
            object-fit: cover;
            /* จัดภาพให้เต็มพื้นที่ */
            margin-right: 0;
            /* รูปติดกัน */
            display: block;
        }

        @keyframes slideRightToLeft {
            0% {
                transform: translateX(0);
            }

            100% {
                transform: translateX(-50%);
            }

            /* เลื่อนไป 50% เพราะเรามีรูป 2 ชุด */
        }

        /* --- Content Layout --- */
        .main-content-wrapper {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 40px;
            padding: 40px 0;
            align-items: start;
        }

        .section-header {
            font-size: 24px;
            margin-bottom: 20px;
            color: #2c3e50;
            border-left: 5px solid var(--primary);
            padding-left: 15px;
            background: white;
            padding-top: 10px;
            padding-bottom: 10px;
            border-radius: 0 8px 8px 0;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .sidebar-left {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .director-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.3s;
        }

        .director-card:hover {
            transform: translateY(-5px);
        }

        .director-card img {
            width: 100%;
            height: 250px;
            object-fit: cover;
        }

        .director-info {
            padding: 15px;
        }

        .director-info h3 {
            font-size: 16px;
            margin-bottom: 5px;
            color: #2c3e50;
        }

        .director-info p {
            color: var(--primary);
            font-size: 12px;
            font-weight: bold;
        }

        .main-right {
            display: flex;
            flex-direction: column;
        }

        .pr-list-large {
            display: flex;
            flex-direction: column;
            gap: 40px;
        }

        .pr-card-large {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
            text-decoration: none;
            color: inherit;
            display: block;
            position: relative;
            overflow: hidden;
            border: 1px solid #eee;
        }

        .pr-card-large:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.12);
        }

        .pr-image-wrapper-large {
            width: 80%;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #f8f8f8;
            position: relative;
            border-bottom: 1px solid #eee;
        }

        .pr-image-wrapper-large img {
            width: 80%;
            height: auto;
            display: block;
            transition: transform 0.5s;
        }

        .pr-date-badge {
            position: absolute;
            top: 15px;
            right: 0;
            background: var(--secondary);
            color: white;
            padding: 8px 15px 8px 10px;
            font-weight: bold;
            font-size: 14px;
            border-radius: 4px 0 0 4px;
            box-shadow: -2px 2px 5px rgba(0, 0, 0, 0.2);
            z-index: 2;
        }

        .pr-content-large {
            padding: 20px;
            background: white;
        }

        .pr-title-large {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        /* --- Announcements --- */
        .announcements-section {
            padding: 50px 0;
            background: white;
            margin-top: 60px;
            border-top: 1px solid #eee;
        }

        .section-title-orange {
            text-align: center;
            margin-bottom: 40px;
            color: var(--primary) !important;
            font-size: 28px;
            font-weight: bold;
            text-transform: uppercase;
            position: relative;
            display: inline-block;
            left: 50%;
            transform: translateX(-50%);
        }

        .section-title-orange i {
            margin-right: 10px;
        }

        .section-title-orange::after {
            content: '';
            display: block;
            width: 60px;
            height: 4px;
            background: var(--primary);
            margin: 10px auto 0;
            border-radius: 2px;
        }

        .announcements-grid-a4 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 40px;
            justify-content: center;
        }

        .announce-card-a4 {
            background: white;
            aspect-ratio: 1 / 1.414;
            display: flex;
            flex-direction: column;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
            position: relative;
            border: 1px solid #eee;
        }

        .announce-card-a4:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .announce-header-orange {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            padding: 15px 20px;
            color: white;
            flex-shrink: 0;
        }

        .announce-date {
            font-size: 11px;
            opacity: 0.9;
            margin-bottom: 5px;
            display: block;
        }

        .announce-title {
            font-size: 16px;
            font-weight: 600;
            line-height: 1.4;
            margin: 0;
        }

        .announce-body {
            padding: 25px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background: #fff;
        }

        .announce-text {
            font-size: 13px;
            color: #555;
            line-height: 1.8;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 6;
            -webkit-box-orient: vertical;
        }

        .attachment-preview {
            margin-top: auto;
            padding-top: 10px;
            font-size: 12px;
        }

        .file-link {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #555;
            background: #f8f9fa;
            padding: 8px;
            border-radius: 4px;
            text-decoration: none;
            border: 1px solid #eee;
            transition: all 0.2s;
        }

        .file-link:hover {
            background: #eef2f8;
            color: var(--primary);
            border-color: var(--primary);
        }

        .announce-footer {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #f0f0f0;
            text-align: right;
        }

        .read-more-link {
            font-size: 12px;
            color: var(--primary);
            text-decoration: none;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .read-more-link:hover {
            color: var(--secondary);
        }

        /* Footer */
        footer {
            background: #2c3e50;
            color: white;
            padding: 40px 20px 20px;
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

        .footer-bottom {
            max-width: 1200px;
            margin: 0 auto;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 20px;
            text-align: center;
            color: #bdc3c7;
            font-size: 13px;
        }

        @media (max-width: 992px) {
            .main-content-wrapper {
                grid-template-columns: 1fr;
            }

            .sidebar-left {
                order: 2;
            }

            .main-right {
                order: 1;
            }

            .director-card {
                max-width: 400px;
                margin: 0 auto;
            }
        }

        @media (max-width: 768px) {
            .hamburger {
                display: flex;
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

            /* ปรับความสูงแบนเนอร์ในมือถือ */
            .banner-slide-img {
                height: 220px;
            }

            .section-title-orange {
                font-size: 22px;
            }

            .announcements-grid-a4 {
                grid-template-columns: 1fr;
                padding: 0 40px;
            }

            .announce-card-a4 {
                aspect-ratio: auto;
                min-height: 350px;
            }
        }

        .tabs-wrapper {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .tab-btn {
            padding: 12px 25px;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 50px;
            cursor: pointer;
            font-size: 16px;
            color: #666;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .tab-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            color: var(--primary);
            border-color: var(--primary);
        }

        .tab-btn.active {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border-color: transparent;
            box-shadow: 0 4px 15px rgba(249, 115, 22, 0.3);
        }

        .tab-content {
            display: none;
            animation: fadeIn 0.5s;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* List Items Design (เหมือนตารางแต่ Responsive กว่า) */
        .list-container {
            max-width: 900px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .list-item {
            display: flex;
            align-items: center;
            background: white;
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border: 1px solid #f3f4f6;
            transition: all 0.3s;
        }

        .list-item:hover {
            transform: translateX(5px);
            border-left: 5px solid var(--primary);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .list-date {
            background: #fdf2f8;
            color: var(--primary);
            padding: 10px;
            border-radius: 8px;
            text-align: center;
            min-width: 70px;
            margin-right: 20px;
            border: 1px solid #fed7aa;
        }

        .list-date .day {
            display: block;
            font-size: 20px;
            font-weight: bold;
            line-height: 1;
        }

        .list-date .month {
            display: block;
            font-size: 12px;
            text-transform: uppercase;
        }

        .list-info {
            flex-grow: 1;
        }

        .list-title {
            text-decoration: none;
            color: #333;
            font-weight: 600;
            font-size: 16px;
            display: block;
            margin-bottom: 5px;
            transition: color 0.2s;
        }

        .list-title:hover {
            color: var(--primary);
        }

        .list-meta {
            font-size: 13px;
            color: #888;
            display: flex;
            gap: 15px;
        }

        .has-file {
            color: #2563eb;
        }

        .btn-read {
            text-decoration: none;
            color: #999;
            font-size: 14px;
            padding: 8px 15px;
            border-radius: 20px;
            background: #f9fafb;
            transition: all 0.3s;
            white-space: nowrap;
        }

        .btn-read:hover {
            background: var(--primary);
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
            background: #f9fafb;
            border-radius: 12px;
            border: 2px dashed #e5e7eb;
        }

        @media (max-width: 600px) {
            .list-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .list-date {
                margin-bottom: 10px;
                display: flex;
                gap: 10px;
                align-items: center;
                width: 100%;
                justify-content: center;
            }

            .btn-read {
                width: 100%;
                text-align: center;
                margin-top: 10px;
            }
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="navbar-container">
            <a href="index.php" class="navbar-brand">
                <?php if ($isLogoFile): ?>
                    <img src="<?php echo $logoData; ?>" alt="Logo" class="navbar-logo-img">
                <?php else: ?>
                    <span style="font-size: 28px;"><?php echo $logoData; ?></span>
                <?php endif; ?>
                <span><?php echo sanitize($orgInfo['name'] ?? 'สถาบันอุตสาหกรรมสุขภาพ'); ?></span>
            </a>

            <ul class="nav-menu">
                <li><a href="#home">หน้าแรก</a></li>
                <li><a href="#pr">ประชาสัมพันธ์</a></li>
                <li><a href="#directors">ผู้บริหาร</a></li>
                <li><a href="#news">ประกาศทั่วไป</a></li>
                <li><a href="admin/login.php">เข้าสู่ระบบ</a></li>
            </ul>
            <div class="hamburger" onclick="toggleMenu()">
                <span></span><span></span><span></span>
            </div>
        </div>
    </nav>

    <section class="banner-section" id="home"
        style="padding: 0; overflow: hidden; background: #f97316; position: relative;">

        <?php
        // ดึงเฉพาะรูปที่ Active เท่านั้น
        $active_banners = $pdo->query("SELECT * FROM banners WHERE status='active' ORDER BY created_at DESC")->fetchAll();
        ?>

        <?php if (!empty($active_banners)): ?>
            <style>
                .banner-slider-wrapper {
                    width: 100%;
                    overflow: hidden;
                    white-space: nowrap;
                    /* บังคับให้รูปเรียงต่อกันแนวนอนไม่ขึ้นบรรทัดใหม่ */
                }

                .banner-track {
                    display: inline-block;
                    animation: marquee 25s linear infinite;
                    /* ความเร็วสไลด์ 25 วินาที */
                }

                /* หยุดสไลด์เมื่อเอาเมาส์ชี้ */
                .banner-track:hover {
                    animation-play-state: paused;
                }

                .banner-item {
                    display: inline-block;
                    height: 450px;
                    /* ความสูงของรูป */
                    width: auto;
                    /* ความกว้างปรับตามสัดส่วน */
                    max-width: 100vw;
                    /* รูปเดียวไม่เกินหน้าจอ */
                    object-fit: cover;
                    vertical-align: top;
                }

                @keyframes marquee {
                    0% {
                        transform: translateX(0);
                    }

                    100% {
                        transform: translateX(-50%);
                    }

                    /* เลื่อนไป 50% เพราะเราเบิ้ลรูปมา 2 ชุด */
                }

                /* ปรับสำหรับมือถือ */
                @media (max-width: 768px) {
                    .banner-item {
                        height: 250px;
                    }
                }
            </style>

            <div class="banner-slider-wrapper">
                <div class="banner-track">
                    <?php foreach ($active_banners as $b): ?>
                        <img src="uploads/banners/<?php echo sanitize($b['image']); ?>" class="banner-item" alt="Banner">
                    <?php endforeach; ?>

                    <?php foreach ($active_banners as $b): ?>
                        <img src="uploads/banners/<?php echo sanitize($b['image']); ?>" class="banner-item" alt="Banner">
                    <?php endforeach; ?>
                </div>
            </div>

        <?php else: ?>
            <div style="color: white; font-size: 20px; text-align: center; padding: 80px; background: #ea580c;">
                <i class="fas fa-image" style="font-size: 48px; margin-bottom: 15px; display: block; opacity: 0.5;"></i>
                ยังไม่มีภาพประชาสัมพันธ์
            </div>
        <?php endif; ?>

    </section>

    <div class="container">
        <div class="main-content-wrapper">

            <aside class="sidebar-left">
                <div class="section-header">
                    <span>👔 ผู้บริหาร</span>
                </div>

                <?php if ($director): ?>
                    <div class="director-card">
                        <?php if ($director['image']): ?>
                            <img src="uploads/directors/<?php echo sanitize($director['image']); ?>"
                                alt="<?php echo sanitize($director['name']); ?>">
                        <?php else: ?>
                            <div
                                style="width: 100%; height: 250px; background: #eee; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-user-tie" style="font-size: 60px; color: #bbb;"></i>
                            </div>
                        <?php endif; ?>
                        <div class="director-info">
                            <h3><?php echo sanitize($director['name']); ?></h3>
                            <p><?php echo sanitize($director['position']); ?></p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="director-card" style="padding: 20px; color: #999;">ยังไม่มีข้อมูลผู้บริหาร</div>
                <?php endif; ?>

                <a href="personnel.php"
                    style="display: block; text-align: center; margin-top: 15px; padding: 10px; background: var(--primary); color: white; border-radius: 8px; text-decoration: none;">
                    <i class="fas fa-users"></i> ดูบุคลากรทั้งหมด
                </a>
            </aside>

            <main class="main-right" id="pr">
                <div class="section-header">
                    <span>📸 ข่าวประชาสัมพันธ์</span>
                </div>

                <div class="pr-list-large">
                    <?php if (!empty($pr_images)): ?>
                        <?php foreach ($pr_images as $img): ?>
                            <a href="uploads/files/<?php echo htmlspecialchars($img['filepath']); ?>" target="_blank"
                                class="pr-card-large">
                                <div class="pr-date-badge">
                                    <?php echo date('Y', strtotime($img['created_at'])); ?>
                                </div>
                                <div class="pr-image-wrapper-large">
                                    <img src="uploads/files/<?php echo htmlspecialchars($img['filepath']); ?>"
                                        alt="<?php echo htmlspecialchars($img['filename']); ?>">
                                </div>
                                <div class="pr-content-large">
                                    <div class="pr-title-large"><?php echo htmlspecialchars($img['filename']); ?></div>
                                    <div style="font-size: 13px; color: #888;">
                                        <i class="far fa-calendar-alt"></i> โพสต์เมื่อ:
                                        <?php echo date('d/m/Y', strtotime($img['created_at'])); ?>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div
                            style="text-align: center; padding: 60px; border: 2px dashed #eee; background: white; border-radius: 8px;">
                            <i class="fas fa-images" style="font-size: 50px; color: #ddd;"></i>
                            <p style="color: #999; margin-top: 15px; font-size: 16px;">ยังไม่มีภาพกิจกรรมประชาสัมพันธ์</p>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <section class="announcements-section" id="news" style="background: #fff; padding: 60px 0;">
        <div class="container">
            <h2 class="section-title-orange">
                <i class="fas fa-newspaper"></i> ศูนย์ข่าวสารและบริการ
            </h2>

            <div class="tabs-wrapper">
                <button class="tab-btn active" onclick="openTab(event, 'tab-download')">
                    <i class="fas fa-download"></i> ดาวน์โหลด
                </button>
                <button class="tab-btn" onclick="openTab(event, 'tab-procurement')">
                    <i class="fas fa-shopping-cart"></i> จัดซื้อ-จัดจ้าง
                </button>
                <button class="tab-btn" onclick="openTab(event, 'tab-ita')">
                    <i class="fas fa-balance-scale"></i> ประกาศ ITA
                </button>
            </div>

            <div id="tab-download" class="tab-content active">
                <div class="list-container">
                    <?php if (!empty($downloads)): ?>
                        <?php foreach ($downloads as $item): ?>
                           
                            <div class="list-item">
                                <div class="list-date">
                                    <span class="day"><?php echo date('d', strtotime($item['created_at'])); ?></span>
                                    <span class="month"><?php echo date('M', strtotime($item['created_at'])); ?></span>
                                </div>
                                <div class="list-info">
                                    <a href="announcement.php?id=<?php echo $item['id']; ?>" class="list-title">
                                        <?php echo htmlspecialchars($item['title']); ?>
                                    </a>
                                    <div class="list-meta">
                                        <i class="far fa-clock"></i>
                                        <?php echo date('d/m/Y', strtotime($item['created_at'])); ?>
                                        <?php if ($item['image']): ?>
                                            <span class="has-file"><i class="fas fa-paperclip"></i> มีไฟล์แนบ</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <a href="announcement.php?id=<?php echo $item['id']; ?>" class="btn-read">
                                    อ่านต่อ <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">ยังไม่มีข้อมูลในหมวดนี้</div>
                    <?php endif; ?>
                </div>
            </div>

            <div id="tab-procurement" class="tab-content">
                <div class="list-container">
                    <?php if (!empty($procurements)): ?>
                        <?php foreach ($procurements as $item): ?>
                            <div class="list-item">
                                <div class="list-date">
                                    <span class="day"><?php echo date('d', strtotime($item['created_at'])); ?></span>
                                    <span class="month"><?php echo date('M', strtotime($item['created_at'])); ?></span>
                                </div>
                                <div class="list-info">
                                    <a href="announcement.php?id=<?php echo $item['id']; ?>" class="list-title">
                                        <?php echo htmlspecialchars($item['title']); ?>
                                    </a>
                                    <div class="list-meta">
                                        <i class="far fa-clock"></i>
                                        <?php echo date('d/m/Y', strtotime($item['created_at'])); ?>
                                        <?php if ($item['image']): ?>
                                            <span class="has-file"><i class="fas fa-paperclip"></i> มีไฟล์แนบ</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <a href="announcement.php?id=<?php echo $item['id']; ?>" class="btn-read">
                                    อ่านต่อ <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">ยังไม่มีข้อมูลประกาศจัดซื้อจัดจ้าง</div>
                    <?php endif; ?>
                </div>
            </div>

            <div id="tab-ita" class="tab-content">
                <div class="list-container">
                    <?php if (!empty($itas)): ?>
                        <?php foreach ($itas as $item): ?>
                            <div class="list-item">
                                <div class="list-date">
                                    <span class="day"><?php echo date('d', strtotime($item['created_at'])); ?></span>
                                    <span class="month"><?php echo date('M', strtotime($item['created_at'])); ?></span>
                                </div>
                                <div class="list-info">
                                    <a href="announcement.php?id=<?php echo $item['id']; ?>" class="list-title">
                                        <?php echo htmlspecialchars($item['title']); ?>
                                    </a>
                                    <div class="list-meta">
                                        <i class="far fa-clock"></i>
                                        <?php echo date('d/m/Y', strtotime($item['created_at'])); ?>
                                        <?php if ($item['image']): ?>
                                            <span class="has-file"><i class="fas fa-paperclip"></i> มีไฟล์แนบ</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <a href="announcement.php?id=<?php echo $item['id']; ?>" class="btn-read">
                                    อ่านต่อ <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">ยังไม่มีข้อมูลประกาศ ITA</div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </section>

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
                        <li><a href="tel:<?php echo htmlspecialchars($orgInfo['phone']); ?>">📱
                                <?php echo sanitize($orgInfo['phone']); ?></a></li>
                    <?php endif; ?>
                    <?php if ($orgInfo && $orgInfo['email']): ?>
                        <li><a href="mailto:<?php echo htmlspecialchars($orgInfo['email']); ?>">✉️
                                <?php echo sanitize($orgInfo['email']); ?></a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="footer-section">
                <h3>เมนูด่วน</h3>
                <ul>
                    <li><a href="#home">หน้าแรก</a></li>
                    <li><a href="#pr">ประชาสัมพันธ์</a></li>
                    <li><a href="#news">ประกาศ</a></li>
                    <li><a href="admin/login.php">สำหรับเจ้าหน้าที่</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025
                <?php if ($isLogoFile): ?>
                    <img src="<?php echo $logoData; ?>" alt="Logo"
                        style="height: 30px; width: auto; vertical-align: middle; margin-right: 5px; border-radius: 4px;">
                <?php else: ?>
                    <span style="margin-right: 5px;"><?php echo $logoData; ?></span>
                <?php endif; ?>
                <?php echo sanitize($orgInfo['name'] ?? 'สถาบันอุตสาหกรรมสุขภาพ'); ?>. All rights reserved.
            </p>
        </div>
    </footer>

    <script>
        function toggleMenu() {
            const menu = document.querySelector('.nav-menu');
            menu.classList.toggle('active');
        }
        document.querySelectorAll('.nav-menu a').forEach(link => {
            link.addEventListener('click', () => {
                document.querySelector('.nav-menu').classList.remove('active');
            });
        });
        function openTab(evt, tabName) {
            var i, tabcontent, tablinks;

            // ซ่อนเนื้อหาทั้งหมด
            tabcontent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].classList.remove("active");
            }

            // เอา class active ออกจากปุ่มทั้งหมด
            tablinks = document.getElementsByClassName("tab-btn");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].classList.remove("active");
            }

            // แสดงเนื้อหา Tab ที่เลือก และใส่ class active ที่ปุ่ม
            document.getElementById(tabName).classList.add("active");
            evt.currentTarget.classList.add("active");
        }
    </script>
</body>

</html>