<?php
// admin/pages/pr_images.php - จัดการรูปประชาสัมพันธ์

// ตรวจสอบ Session
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

$message = '';

// จัดการการอัปโหลดและลบ
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. อัปโหลดรูปภาพ
    if ($action == 'upload' && isset($_FILES['image'])) {
        $file = $_FILES['image'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExt = ['jpg', 'jpeg', 'png', 'gif'];
        
        // ชื่อไฟล์ (Description) ที่ผู้ใช้กรอก ถ้าไม่มีให้ใช้ชื่อไฟล์เดิม
        $file_desc = $_POST['description'] ?? $file['name'];

        if (in_array($ext, $allowedExt)) {
            $filename = time() . '_' . uniqid() . '.' . $ext;
            $filepath = '../uploads/files/' . $filename;
            
            // ตรวจสอบว่ามีโฟลเดอร์หรือไม่
            if (!is_dir('../uploads/files/')) {
                mkdir('../uploads/files/', 0755, true);
            }

            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                // บันทึกลงตาราง files แต่ระบุ category เป็น 'pr_activity'
                $stmt = $pdo->prepare(
                    "INSERT INTO files (filename, filepath, file_type, category, status, created_at) 
                     VALUES (?, ?, ?, 'pr_activity', 'active', NOW())"
                );
                // ใช้ชื่อไฟล์ที่อัปโหลดเป็นชื่อแสดงผล หรือจะใช้ชื่อที่ตั้งเองก็ได้
                $stmt->execute([$file_desc, $filename, $ext]);
                
                $message = "<div class='alert alert-success'>✓ อัปโหลดรูปประชาสัมพันธ์สำเร็จ</div>";
            } else {
                $message = "<div class='alert alert-error'>✗ เกิดข้อผิดพลาดในการย้ายไฟล์</div>";
            }
        } else {
            $message = "<div class='alert alert-error'>✗ อนุญาตเฉพาะไฟล์รูปภาพ (JPG, PNG, GIF) เท่านั้น</div>";
        }
    }

    // 2. ลบรูปภาพ
    elseif ($action == 'delete') {
        $id = $_POST['id'] ?? '';
        
        // ดึงข้อมูลไฟล์เพื่อลบไฟล์จริง
        $stmt = $pdo->prepare("SELECT filepath FROM files WHERE id = ? AND category = 'pr_activity'");
        $stmt->execute([$id]);
        $file = $stmt->fetch();

        if ($file) {
            if (file_exists('../uploads/files/' . $file['filepath'])) {
                unlink('../uploads/files/' . $file['filepath']);
            }
            
            $delStmt = $pdo->prepare("DELETE FROM files WHERE id = ?");
            $delStmt->execute([$id]);
            
            $message = "<div class='alert alert-success'>✓ ลบรูปภาพสำเร็จ</div>";
        }
    }
}

// ดึงรายการรูปประชาสัมพันธ์ (เฉพาะหมวด pr_activity)
$pr_images = $pdo->query("SELECT * FROM files WHERE category = 'pr_activity' ORDER BY created_at DESC")->fetchAll();
?>

<style>
    .admin-form {
        background: white;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 30px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .pr-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    
    .pr-card {
        background: white;
        border: 1px solid #eee;
        border-radius: 8px;
        overflow: hidden;
        transition: all 0.3s;
        position: relative;
    }
    
    .pr-card:hover {
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }
    
    .pr-img-wrapper {
        height: 150px;
        overflow: hidden;
        background: #f9f9f9;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .pr-img-wrapper img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .pr-info {
        padding: 10px;
    }
    
    .pr-name {
        font-size: 13px;
        font-weight: bold;
        margin-bottom: 5px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .pr-date {
        font-size: 11px;
        color: #888;
        margin-bottom: 10px;
    }
    
    .btn-delete {
        width: 100%;
        background: #fff0f0;
        color: #dc3545;
        border: 1px solid #ffc9c9;
        padding: 5px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
    }
    
    .btn-delete:hover {
        background: #dc3545;
        color: white;
    }

    .alert { padding: 12px; border-radius: 5px; margin-bottom: 20px; }
    .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
</style>

<h2>🖼️ จัดการรูปประชาสัมพันธ์ (Activity Gallery)</h2>

<?php echo $message; ?>

<div class="admin-form">
    <h3>📤 อัปโหลดรูปกิจกรรมใหม่</h3>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="upload">
        
        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">เลือกรูปภาพ:</label>
            <input type="file" name="image" accept="image/*" required 
                   style="padding: 10px; border: 1px solid #ddd; width: 100%; border-radius: 5px;">
        </div>
        
        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">คำอธิบายรูปภาพ (Optional):</label>
            <input type="text" name="description" placeholder="เช่น กิจกรรมวันสำคัญ..." 
                   style="padding: 10px; border: 1px solid #ddd; width: 100%; border-radius: 5px;">
        </div>
        
        <button type="submit" class="btn btn-primary">บันทึกรูปภาพ</button>
    </form>
</div>

<h3>📋 รายการรูปภาพปัจจุบัน (<?php echo count($pr_images); ?>)</h3>

<?php if (empty($pr_images)): ?>
    <div style="text-align: center; padding: 40px; background: white; border-radius: 8px; color: #999;">
        ยังไม่มีรูปภาพประชาสัมพันธ์
    </div>
<?php else: ?>
    <div class="pr-grid">
        <?php foreach ($pr_images as $img): ?>
            <div class="pr-card">
                <div class="pr-img-wrapper">
                    <img src="../uploads/files/<?php echo htmlspecialchars($img['filepath']); ?>" alt="Activity">
                </div>
                <div class="pr-info">
                    <div class="pr-name" title="<?php echo htmlspecialchars($img['filename']); ?>">
                        <?php echo htmlspecialchars($img['filename']); ?>
                    </div>
                    <div class="pr-date">
                        📅 <?php echo date('d/m/Y H:i', strtotime($img['created_at'])); ?>
                    </div>
                    <form method="POST" onsubmit="return confirm('ต้องการลบรูปนี้ใช่หรือไม่?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo $img['id']; ?>">
                        <button type="submit" class="btn-delete">🗑️ ลบรูปภาพ</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>