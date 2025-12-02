<?php
session_start();
require '../config.php';

// ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$page = $_GET['page'] ?? 'dashboard';
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการเว็บไซต์</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="admin-header">
                <h2>⚙️ ระบบจัดการ</h2>
                <p style="font-size: 12px; color: #bdc3c7; margin-top: 8px;">
                    ยินดีต้อนรับ, <?php echo htmlspecialchars($_SESSION['username']); ?>
                </p>
            </div>

            <ul class="admin-menu">
                <li><a href="?page=dashboard">📊 แดชบอร์ด</a></li>
                <li><a href="?page=organization">🏢 ข้อมูลหน่วยงาน</a></li>
                <li><a href="?page=banners">📸 จัดการแบนเนอร์</a></li>
                <li><a href="?page=directors">👔 จัดการผู้บริหาร</a></li>
                <li><a href="?page=announcements">📢 จัดการประกาศ</a></li>
                <li><a href="logout.php">🚪 ออกจากระบบ</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <?php
            // โหลดไฟล์หน้าต่างๆ
            $pagePath = __DIR__ . "/pages/{$page}.php";

            if (file_exists($pagePath)) {
                include $pagePath;
            } else {
                include __DIR__ . "/pages/dashboard.php";
            }
            ?>
        </div>
    </div>

    <script src="../assets/js/jquery.min.js"></script>
    <script src="../assets/js/admin.js"></script>
</body>

</html>