<?php
// ตรวจสอบ Session
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

// ดึงสถิติต่างๆ
try {
    $bannerCount = $pdo->query("SELECT COUNT(*) as count FROM banners")->fetch()['count'];
    $directorCount = $pdo->query("SELECT COUNT(*) as count FROM directors WHERE status='active'")->fetch()['count'];
    $announcementCount = $pdo->query("SELECT COUNT(*) as count FROM announcements WHERE status='active'")->fetch()['count'];
    $fileCount = $pdo->query("SELECT COUNT(*) as count FROM files WHERE status='active'")->fetch()['count'];

    // ประกาศล่าสุด
    $recentAnnouncements = $pdo->query(
        "SELECT * FROM announcements ORDER BY created_at DESC LIMIT 5"
    )->fetchAll();

    // ไฟล์ล่าสุด
    $recentFiles = $pdo->query(
        "SELECT * FROM files WHERE status='active' ORDER BY created_at DESC LIMIT 5"
    )->fetchAll();

} catch (Exception $e) {
    $bannerCount = $directorCount = $announcementCount = $fileCount = 0;
    $recentAnnouncements = $recentFiles = [];
}
?>

<style>
    .dashboard-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px;
        border-radius: 8px;
        margin-bottom: 30px;
    }

    .dashboard-header h1 {
        margin: 0;
        font-size: 28px;
    }

    .dashboard-header p {
        margin: 5px 0 0 0;
        opacity: 0.9;
        font-size: 14px;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        padding: 25px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        border-left: 4px solid #667eea;
        transition: all 0.3s;
        text-decoration: none;
        color: inherit;
        cursor: pointer;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .stat-card h3 {
        color: #666;
        font-size: 12px;
        text-transform: uppercase;
        margin: 0 0 10px 0;
        letter-spacing: 0.5px;
    }

    .stat-card .number {
        font-size: 32px;
        font-weight: bold;
        color: #667eea;
    }

    .stat-card.banners {
        border-left-color: #f97316;
    }

    .stat-card.banners .number {
        color: #f97316;
    }

    .stat-card.directors {
        border-left-color: #667eea;
    }

    .stat-card.directors .number {
        color: #667eea;
    }

    .stat-card.announcements {
        border-left-color: #28a745;
    }

    .stat-card.announcements .number {
        color: #28a745;
    }

    .stat-card.files {
        border-left-color: #dc3545;
    }

    .stat-card.files .number {
        color: #dc3545;
    }

    .two-column {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-top: 30px;
    }

    .card {
        background: white;
        padding: 25px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .card h2 {
        margin-top: 0;
        color: #333;
        border-bottom: 2px solid #667eea;
        padding-bottom: 10px;
        font-size: 18px;
        margin-bottom: 15px;
    }

    .list-item {
        padding: 12px 0;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 10px;
    }

    .list-item:last-child {
        border-bottom: none;
    }

    .list-item-content {
        flex: 1;
        min-width: 0;
    }

    .list-item strong {
        color: #333;
        display: block;
        font-size: 14px;
        word-break: break-word;
    }

    .list-item small {
        color: #999;
        font-size: 12px;
        display: block;
        margin-top: 4px;
    }

    .badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: bold;
        white-space: nowrap;
        margin-top: 4px;
    }

    .badge-active {
        background: #d4edda;
        color: #155724;
    }

    .badge-file {
        background: #cfe2ff;
        color: #084298;
    }

    .badge-pdf {
        background: #f8d7da;
        color: #721c24;
    }

    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 10px;
        margin-top: 20px;
    }

    .quick-btn {
        padding: 10px;
        background: #667eea;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        text-decoration: none;
        text-align: center;
        font-size: 13px;
        transition: background 0.3s;
    }

    .quick-btn:hover {
        background: #5568d3;
    }

    .quick-btn.secondary {
        background: #6c757d;
    }

    .quick-btn.secondary:hover {
        background: #5a6268;
    }

    .view-all-btn {
        display: inline-block;
        margin-top: 15px;
    }

    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .two-column {
            grid-template-columns: 1fr;
        }

        .dashboard-header {
            padding: 20px;
        }

        .dashboard-header h1 {
            font-size: 22px;
        }

        .list-item {
            flex-direction: column;
        }
    }

    @media (max-width: 480px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }

        .stat-card {
            padding: 15px;
        }

        .stat-card .number {
            font-size: 24px;
        }
    }
</style>

<div class="dashboard-header">
    <h1>👋 ยินดีต้อนรับ, <?php echo htmlspecialchars($_SESSION['username']); ?></h1>
    <p>📅 <?php echo date('วันที่ d/m/Y เวลา H:i:s', time()); ?></p>
</div>

<!-- สถิติ -->
<div class="stats-grid">
    <a href="?page=banners" class="stat-card banners">
        <h3>📸 แบนเนอร์</h3>
        <div class="number"><?php echo $bannerCount; ?></div>
    </a>

    <a href="?page=directors" class="stat-card directors">
        <h3>👔 ผู้บริหาร</h3>
        <div class="number"><?php echo $directorCount; ?></div>
    </a>

    <a href="?page=announcements" class="stat-card announcements">
        <h3>📢 ประกาศ</h3>
        <div class="number"><?php echo $announcementCount; ?></div>
    </a>

    <a href="?page=announcements" class="stat-card files">
        <h3>📄 ไฟล์</h3>
        <div class="number"><?php echo $fileCount; ?></div>
    </a>
</div>

<!-- การกระทำด่วน -->
<div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none;">
    <h2 style="border-bottom-color: rgba(255,255,255,0.3); color: white; margin-bottom: 10px;">⚡ การกระทำด่วน</h2>
    <div class="quick-actions">
        <a href="?page=banners" class="quick-btn">📸 แบนเนอร์</a>
        <a href="?page=directors" class="quick-btn">👔 ผู้บริหาร</a>
        <a href="?page=announcements" class="quick-btn">📢 ประกาศ & ไฟล์</a>
    </div>
</div>

<!-- ประกาศและไฟล์ -->
<div class="two-column">
    <div class="card">
        <h2>📰 ประกาศล่าสุด</h2>
        <?php if (empty($recentAnnouncements)): ?>
            <p style="color: #999; text-align: center; padding: 20px;">ยังไม่มีประกาศ</p>
        <?php else: ?>
            <?php foreach ($recentAnnouncements as $announce): ?>
                <div class="list-item">
                    <div class="list-item-content">
                        <strong><?php echo htmlspecialchars(substr($announce['title'], 0, 40)); ?></strong>
                        <small>
                            📅 <?php echo date('d/m/Y H:i', strtotime($announce['created_at'])); ?>
                        </small>
                        <div class="badge badge-active">✓ เปิดใช้งาน</div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <a href="?page=announcements" class="quick-btn secondary view-all-btn"
            style="display: block; text-align: center;">ดูทั้งหมด →</a>
    </div>

    <div class="card">
        <h2>📄 ไฟล์ล่าสุด</h2>
        <?php if (empty($recentFiles)): ?>
            <p style="color: #999; text-align: center; padding: 20px;">ยังไม่มีไฟล์</p>
        <?php else: ?>
            <?php foreach ($recentFiles as $file): ?>
                <div class="list-item">
                    <div class="list-item-content">
                        <strong style="display: flex; align-items: center; gap: 8px;">
                            <?php
                            $icons = [
                                'pdf' => '📄',
                                'doc' => '📝',
                                'docx' => '📝',
                                'xls' => '📊',
                                'xlsx' => '📊',
                                'ppt' => '📑',
                                'pptx' => '📑',
                                'jpg' => '🖼️',
                                'png' => '🖼️',
                                'zip' => '🗜️'
                            ];
                            $ext = strtolower($file['file_type']);
                            echo $icons[$ext] ?? '📦';
                            ?>
                            <?php echo htmlspecialchars(substr($file['filename'], 0, 35)); ?>
                        </strong>
                        <small>
                            📅 <?php echo date('d/m/Y', strtotime($file['created_at'])); ?>
                            <span style="margin-left: 8px;">📁 <?php echo strtoupper($file['category']); ?></span>
                        </small>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <a href="?page=announcements" class="quick-btn secondary view-all-btn"
            style="display: block; text-align: center;">ดูทั้งหมด →</a>
    </div>
</div>