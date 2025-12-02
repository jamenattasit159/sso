


<!-- ไฟล์ admin/pages/banners.php - จัดการแบนเนอร์ -->
<?php
// จัดการอัพโหลดแบนเนอร์
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if($_POST['action'] == 'upload' && isset($_FILES['image'])) {
        $file = $_FILES['image'];
        $filename = time() . '_' . basename($file['name']);
        $filepath = '../uploads/banners/' . $filename;
        
        if(move_uploaded_file($file['tmp_name'], $filepath)) {
            // อัพเดทฐานข้อมูล
            $stmt = $pdo->prepare("UPDATE banners SET status='inactive' WHERE status='active'");
            $stmt->execute();
            
            $stmt = $pdo->prepare("INSERT INTO banners (image, status, created_at) VALUES (?, 'active', NOW())");
            $stmt->execute([$filename]);
            
            echo "<p class='success'>อัพโหลดแบนเนอร์เสร็จแล้ว</p>";
        }
    }
}

$banners = $pdo->query("SELECT * FROM banners ORDER BY created_at DESC")->fetchAll();
?>

<div class="admin-section">
    <h2>จัดการแบนเนอร์</h2>
    
    <form method="POST" enctype="multipart/form-data" class="form-upload">
        <input type="hidden" name="action" value="upload">
        <div class="form-group">
            <label>เลือกรูปภาพแบนเนอร์:</label>
            <input type="file" name="image" accept="image/*" required>
        </div>
        <button type="submit" class="btn btn-primary">อัพโหลด</button>
    </form>

    <h3>แบนเนอร์ปัจจุบัน</h3>
    <table class="admin-table">
        <tr>
            <th>รูปภาพ</th>
            <th>สถานะ</th>
            <th>วันที่สร้าง</th>
            <th>ลบ</th>
        </tr>
        <?php foreach($banners as $banner): ?>
            <tr>
                <td><img src="../uploads/banners/<?php echo htmlspecialchars($banner['image']); ?>" 
                         style="max-width: 100px; height: auto;"></td>
                <td><?php echo $banner['status']; ?></td>
                <td><?php echo date('d/m/Y H:i', strtotime($banner['created_at'])); ?></td>
                <td>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo $banner['id']; ?>">
                        <button type="submit" class="btn btn-danger" onclick="return confirm('ต้องการลบหรือไม่?')">ลบ</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>