<?php
// ตรวจสอบ Session
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

$message = '';

// จัดการข้อมูลผู้บริหาร
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'add') {
        $name = $_POST['name'] ?? '';
        $position = $_POST['position'] ?? '';
        $description = $_POST['description'] ?? '';
        $image = '';
        
        if (empty($name) || empty($position)) {
            $message = '<div class="alert alert-error">✗ กรุณากรอกชื่อและตำแหน่ง</div>';
        } else {
            // จัดการไฟล์
            if (isset($_FILES['image']) && $_FILES['image']['size'] > 0) {
                $file = $_FILES['image'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowedExt = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array($ext, $allowedExt)) {
                    $filename = time() . '_' . md5(uniqid()) . '.' . $ext;
                    $uploadPath = '../uploads/directors/';
                    
                    if (!is_dir($uploadPath)) {
                        mkdir($uploadPath, 0755, true);
                    }
                    
                    if (move_uploaded_file($file['tmp_name'], $uploadPath . $filename)) {
                        $image = $filename;
                    }
                }
            }
            
            try {
                $stmt = $pdo->prepare(
                    "INSERT INTO directors (name, position, description, image, status, created_at) 
                     VALUES (?, ?, ?, ?, 'active', NOW())"
                );
                $stmt->execute([$name, $position, $description, $image]);
                $message = '<div class="alert alert-success">✓ เพิ่มผู้บริหารเสร็จแล้ว</div>';
            } catch (Exception $e) {
                $message = '<div class="alert alert-error">✗ เกิดข้อผิดพลาด: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        }
    }
    
    elseif ($action == 'update') {
        $id = $_POST['id'] ?? '';
        $name = $_POST['name'] ?? '';
        $position = $_POST['position'] ?? '';
        $description = $_POST['description'] ?? '';
        
        if (empty($id) || empty($name) || empty($position)) {
            $message = '<div class="alert alert-error">✗ ข้อมูลไม่ครบ</div>';
        } else {
            try {
                // ตรวจสอบไฟล์ใหม่
                if (isset($_FILES['image']) && $_FILES['image']['size'] > 0) {
                    $file = $_FILES['image'];
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $allowedExt = ['jpg', 'jpeg', 'png', 'gif'];
                    
                    if (in_array($ext, $allowedExt)) {
                        $filename = time() . '_' . md5(uniqid()) . '.' . $ext;
                        $uploadPath = '../uploads/directors/';
                        
                        if (move_uploaded_file($file['tmp_name'], $uploadPath . $filename)) {
                            // ลบไฟล์เก่า
                            $oldDirector = $pdo->query("SELECT image FROM directors WHERE id=$id")->fetch();
                            if ($oldDirector['image'] && file_exists('../uploads/directors/' . $oldDirector['image'])) {
                                @unlink('../uploads/directors/' . $oldDirector['image']);
                            }
                            
                            $stmt = $pdo->prepare(
                                "UPDATE directors SET name=?, position=?, description=?, image=?, updated_at=NOW() WHERE id=?"
                            );
                            $stmt->execute([$name, $position, $description, $filename, $id]);
                        }
                    }
                } else {
                    $stmt = $pdo->prepare(
                        "UPDATE directors SET name=?, position=?, description=?, updated_at=NOW() WHERE id=?"
                    );
                    $stmt->execute([$name, $position, $description, $id]);
                }
                $message = '<div class="alert alert-success">✓ อัพเดทผู้บริหารเสร็จแล้ว</div>';
                unset($_GET['edit']);
            } catch (Exception $e) {
                $message = '<div class="alert alert-error">✗ เกิดข้อผิดพลาด</div>';
            }
        }
    }
    
    elseif ($action == 'delete') {
        $id = $_POST['id'] ?? '';
        
        try {
            $director = $pdo->query("SELECT image FROM directors WHERE id=$id")->fetch();
            
            if ($director && $director['image'] && file_exists('../uploads/directors/' . $director['image'])) {
                @unlink('../uploads/directors/' . $director['image']);
            }
            
            $stmt = $pdo->prepare("DELETE FROM directors WHERE id=?");
            $stmt->execute([$id]);
            $message = '<div class="alert alert-success">✓ ลบผู้บริหารเสร็จแล้ว</div>';
        } catch (Exception $e) {
            $message = '<div class="alert alert-error">✗ ไม่สามารถลบได้</div>';
        }
    }
}

$directors = $pdo->query("SELECT * FROM directors ORDER BY created_at DESC")->fetchAll();
$editId = $_GET['edit'] ?? '';
$editDirector = null;

if ($editId) {
    $editDirector = $pdo->query("SELECT * FROM directors WHERE id=" . intval($editId))->fetch();
}
?>

<style>
    .admin-form {
        background: white;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 30px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
        margin-bottom: 15px;
    }
    
    .form-group {
        margin-bottom: 15px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: bold;
        color: #333;
    }
    
    .form-group input,
    .form-group textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 14px;
        font-family: inherit;
    }
    
    .form-group textarea {
        resize: vertical;
        min-height: 100px;
    }
    
    .form-group input:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 5px rgba(102, 126, 234, 0.5);
    }
    
    .btn-group {
        display: flex;
        gap: 10px;
    }
    
    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-weight: bold;
        transition: background 0.3s;
    }
    
    .btn-primary {
        background: #667eea;
        color: white;
    }
    
    .btn-primary:hover {
        background: #5568d3;
    }
    
    .btn-secondary {
        background: #6c757d;
        color: white;
    }
    
    .btn-secondary:hover {
        background: #5a6268;
    }
    
    .btn-danger {
        background: #dc3545;
        color: white;
    }
    
    .btn-danger:hover {
        background: #c82333;
    }
    
    .alert {
        padding: 12px;
        border-radius: 5px;
        margin-bottom: 20px;
    }
    
    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .alert-error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    .directors-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .directors-table thead {
        background: #f8f9fa;
    }
    
    .directors-table th,
    .directors-table td {
        padding: 15px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }
    
    .directors-table tbody tr:hover {
        background: #f8f9fa;
    }
    
    .director-img {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        object-fit: cover;
    }
    
    .action-buttons {
        display: flex;
        gap: 5px;
    }
    
    .btn-sm {
        padding: 6px 12px;
        font-size: 12px;
    }
    
    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
        }
        
        .action-buttons {
            flex-direction: column;
        }
        
        .directors-table {
            font-size: 12px;
        }
        
        .directors-table th,
        .directors-table td {
            padding: 10px;
        }
    }
</style>

<h2>👔 จัดการผู้บริหาร</h2>

<?php echo $message; ?>

<div class="admin-form">
    <h3><?php echo $editDirector ? 'แก้ไขผู้บริหาร' : 'เพิ่มผู้บริหารใหม่'; ?></h3>
    
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="<?php echo $editDirector ? 'update' : 'add'; ?>">
        <?php if ($editDirector): ?>
            <input type="hidden" name="id" value="<?php echo $editDirector['id']; ?>">
        <?php endif; ?>
        
        <div class="form-row">
            <div class="form-group">
                <label>ชื่อ - สกุล *</label>
                <input type="text" name="name" 
                       value="<?php echo htmlspecialchars($editDirector['name'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label>ตำแหน่ง *</label>
                <input type="text" name="position" 
                       value="<?php echo htmlspecialchars($editDirector['position'] ?? ''); ?>" required>
            </div>
        </div>
        
        <div class="form-group">
            <label>คำอธิบาย / ประวัติ</label>
            <textarea name="description"><?php echo htmlspecialchars($editDirector['description'] ?? ''); ?></textarea>
        </div>
        
        <div class="form-group">
            <label>รูปภาพ (jpg, png, gif)</label>
            <input type="file" name="image" accept="image/*">
            <?php if ($editDirector && $editDirector['image']): ?>
                <small style="color: #666;">รูปภาพปัจจุบัน: <?php echo htmlspecialchars($editDirector['image']); ?></small>
            <?php endif; ?>
        </div>
        
        <div class="btn-group">
            <button type="submit" class="btn btn-primary">
                <?php echo $editDirector ? 'อัพเดท' : 'เพิ่ม'; ?>
            </button>
            <?php if ($editDirector): ?>
                <a href="?page=directors" class="btn btn-secondary" style="text-decoration: none;">ยกเลิก</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<h3>รายการผู้บริหาร (<?php echo count($directors); ?>)</h3>
<div style="overflow-x: auto;">
    <table class="directors-table">
        <thead>
            <tr>
                <th>รูปภาพ</th>
                <th>ชื่อ - สกุล</th>
                <th>ตำแหน่ง</th>
                <th>สถานะ</th>
                <th>วันที่</th>
                <th>การดำเนิน</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($directors)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; color: #999; padding: 30px;">
                        ยังไม่มีผู้บริหาร
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($directors as $director): ?>
                    <tr>
                        <td>
                            <?php if ($director['image']): ?>
                                <img src="../uploads/directors/<?php echo htmlspecialchars($director['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($director['name']); ?>" class="director-img">
                            <?php else: ?>
                                <div class="director-img" style="background: #ddd; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-user" style="font-size: 28px; color: #999;"></i>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($director['name']); ?></td>
                        <td><?php echo htmlspecialchars($director['position']); ?></td>
                        <td>
                            <span style="background: #d4edda; padding: 4px 8px; border-radius: 4px; font-size: 12px; color: #155724;">
                                ✓ <?php echo $director['status']; ?>
                            </span>
                        </td>
                        <td><?php echo date('d/m/Y', strtotime($director['created_at'])); ?></td>
                        <td>
                            <div class="action-buttons">
                                <a href="?page=directors&edit=<?php echo $director['id']; ?>" 
                                   class="btn btn-primary btn-sm">แก้ไข</a>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $director['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" 
                                            onclick="return confirm('ต้องการลบ <?php echo htmlspecialchars($director['name']); ?> หรือไม่?')">
                                        ลบ
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>