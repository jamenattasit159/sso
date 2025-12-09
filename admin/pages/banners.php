<?php
// จัดการคำสั่งต่างๆ
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    
    // 1. อัปโหลดรูปภาพใหม่
    if($_POST['action'] == 'upload' && isset($_FILES['image'])) {
        $file = $_FILES['image'];
        $filename = time() . '_' . basename($file['name']);
        $filepath = '../uploads/banners/' . $filename;
        
        if(move_uploaded_file($file['tmp_name'], $filepath)) {
            // *** แก้ไข: ลบบรรทัดที่สั่ง UPDATE inactive ทิ้งไป เพื่อให้รูปเก่าไม่ถูกปิด ***
            // $stmt = $pdo->prepare("UPDATE banners SET status='inactive' WHERE status='active'");
            // $stmt->execute();
            
            $stmt = $pdo->prepare("INSERT INTO banners (image, status, created_at) VALUES (?, 'active', NOW())");
            $stmt->execute([$filename]);
            
            echo "<div class='alert alert-success'>✓ อัปโหลดสำเร็จ (เปิดใช้งานทันที)</div>";
        }
    }

    // 2. สลับสถานะ เปิด/ปิด (เพิ่มส่วนนี้)
    elseif($_POST['action'] == 'toggle_status') {
        $id = $_POST['id'];
        $current = $_POST['current_status'];
        $new_status = ($current == 'active') ? 'inactive' : 'active';
        
        $stmt = $pdo->prepare("UPDATE banners SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $id]);
        
        echo "<div class='alert alert-success'>✓ เปลี่ยนสถานะเป็น " . strtoupper($new_status) . " เรียบร้อย</div>";
    }

    // 3. ลบรูปภาพ
    elseif($_POST['action'] == 'delete') {
        $id = $_POST['id'];
        // ลบไฟล์จริง (ถ้าต้องการ)
        $banner = $pdo->query("SELECT image FROM banners WHERE id=$id")->fetch();
        if($banner && file_exists('../uploads/banners/'.$banner['image'])) {
            @unlink('../uploads/banners/'.$banner['image']);
        }
        
        $stmt = $pdo->prepare("DELETE FROM banners WHERE id = ?");
        $stmt->execute([$id]);
        echo "<div class='alert alert-success'>✓ ลบรูปภาพเรียบร้อย</div>";
    }
}

$banners = $pdo->query("SELECT * FROM banners ORDER BY created_at DESC")->fetchAll();
?>

<div class="admin-section">
    <h2>📸 จัดการแบนเนอร์สไลด์</h2>
    
    <div class="card" style="padding: 20px; margin-bottom: 20px;">
        <form method="POST" enctype="multipart/form-data" class="form-upload">
            <input type="hidden" name="action" value="upload">
            <div style="display: flex; gap: 10px; align-items: center;">
                <div style="flex-grow: 1;">
                    <label style="font-weight: bold;">เพิ่มรูปใหม่:</label>
                    <input type="file" name="image" accept="image/*" required class="form-control">
                </div>
                <button type="submit" class="btn btn-primary">⬆️ อัปโหลด</button>
            </div>
        </form>
    </div>

    <h3>รายการแบนเนอร์ทั้งหมด</h3>
    <table class="table" style="background: white; border-radius: 8px; overflow: hidden;">
        <thead style="background: #f1f5f9;">
            <tr>
                <th width="150">ตัวอย่าง</th>
                <th>ชื่อไฟล์</th>
                <th>สถานะ (คลิกเพื่อเปลี่ยน)</th>
                <th>จัดการ</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($banners as $banner): ?>
                <tr>
                    <td>
                        <img src="../uploads/banners/<?php echo htmlspecialchars($banner['image']); ?>" 
                             style="width: 120px; height: 60px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;">
                    </td>
                    <td style="font-size: 12px; color: #666;">
                        <?php echo htmlspecialchars($banner['image']); ?><br>
                        <small>วันที่: <?php echo date('d/m/Y', strtotime($banner['created_at'])); ?></small>
                    </td>
                    <td>
                        <form method="POST">
                            <input type="hidden" name="action" value="toggle_status">
                            <input type="hidden" name="id" value="<?php echo $banner['id']; ?>">
                            <input type="hidden" name="current_status" value="<?php echo $banner['status']; ?>">
                            
                            <?php if($banner['status'] == 'active'): ?>
                                <button type="submit" class="btn btn-sm" style="background: #dcfce7; color: #166534; border: 1px solid #bbf7d0;">
                                    🟢 กำลังแสดงผล (คลิกเพื่อปิด)
                                </button>
                            <?php else: ?>
                                <button type="submit" class="btn btn-sm" style="background: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1;">
                                    ⚪ ปิดใช้งาน (คลิกเพื่อเปิด)
                                </button>
                            <?php endif; ?>
                        </form>
                    </td>
                    <td>
                        <form method="POST" onsubmit="return confirm('ยืนยันการลบรูปนี้?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $banner['id']; ?>">
                            <button type="submit" class="btn btn-danger btn-sm">🗑️ ลบ</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>