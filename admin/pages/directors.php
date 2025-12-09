<?php
// admin/pages/directors.php

// ตรวจสอบ Session
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

$message = '';

// จัดการข้อมูล
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    // --- ส่วน ADD (เพิ่ม) ---
    if ($action == 'add') {
        $name = $_POST['name'] ?? '';
        $position = $_POST['position'] ?? '';
        $category = $_POST['category'] ?? 'personnel'; // รับค่าหมวดหมู่
        $description = $_POST['description'] ?? '';
        $image = '';

        if (empty($name) || empty($position)) {
            $message = '<div class="alert alert-error">✗ กรุณากรอกชื่อและตำแหน่ง</div>';
        } else {
            // จัดการไฟล์ (เหมือนเดิม)
            if (isset($_FILES['image']) && $_FILES['image']['size'] > 0) {
                $file = $_FILES['image'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowedExt = ['jpg', 'jpeg', 'png', 'gif'];
                if (in_array($ext, $allowedExt)) {
                    $filename = time() . '_' . md5(uniqid()) . '.' . $ext;
                    $uploadPath = '../uploads/directors/';
                    if (!is_dir($uploadPath))
                        mkdir($uploadPath, 0755, true);
                    if (move_uploaded_file($file['tmp_name'], $uploadPath . $filename)) {
                        $image = $filename;
                    }
                }
            }

            // เพิ่ม category ลงใน SQL
            $stmt = $pdo->prepare(
                "INSERT INTO directors (name, position, description, image, category, status, created_at) 
                 VALUES (?, ?, ?, ?, ?, 'active', NOW())"
            );
            $stmt->execute([$name, $position, $description, $image, $category]);
            $message = '<div class="alert alert-success">✓ เพิ่มข้อมูลเสร็จแล้ว</div>';
        }
    }

    // --- ส่วน UPDATE (แก้ไข) ---
    elseif ($action == 'update') {
        $id = $_POST['id'];
        $name = $_POST['name'];
        $position = $_POST['position'];
        $category = $_POST['category']; // รับค่าหมวดหมู่
        $description = $_POST['description'];

        // เช็ครูปใหม่ (เหมือนเดิม)
        if (isset($_FILES['image']) && $_FILES['image']['size'] > 0) {
            $file = $_FILES['image'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $filename = time() . '_' . md5(uniqid()) . '.' . $ext;
            move_uploaded_file($file['tmp_name'], '../uploads/directors/' . $filename);

            $stmt = $pdo->prepare("UPDATE directors SET name=?, position=?, description=?, image=?, category=?, updated_at=NOW() WHERE id=?");
            $stmt->execute([$name, $position, $description, $filename, $category, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE directors SET name=?, position=?, description=?, category=?, updated_at=NOW() WHERE id=?");
            $stmt->execute([$name, $position, $description, $category, $id]);
        }
        $message = '<div class="alert alert-success">✓ อัปเดตข้อมูลเสร็จแล้ว</div>';
        unset($_GET['edit']);
    }

    // --- ส่วน DELETE (ลบ) ---
    elseif ($action == 'delete') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM directors WHERE id=?");
        $stmt->execute([$id]);
        $message = '<div class="alert alert-success">✓ ลบข้อมูลเสร็จแล้ว</div>';
    }
}

$directors = $pdo->query("SELECT * FROM directors ORDER BY category ASC, created_at DESC")->fetchAll();
$editId = $_GET['edit'] ?? '';
$editDirector = $editId ? $pdo->query("SELECT * FROM directors WHERE id=" . intval($editId))->fetch() : null;
?>

<h2>👔 จัดการผู้บริหารและบุคลากร</h2>
<?php echo $message; ?>

<div class="admin-form">
    <h3><?php echo $editDirector ? 'แก้ไขข้อมูล' : 'เพิ่มบุคลากรใหม่'; ?></h3>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="<?php echo $editDirector ? 'update' : 'add'; ?>">
        <?php if ($editDirector): ?><input type="hidden" name="id"
                value="<?php echo $editDirector['id']; ?>"><?php endif; ?>

        <div class="form-row">
            <div class="form-group">
                <label>ชื่อ - สกุล *</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($editDirector['name'] ?? ''); ?>"
                    required>
            </div>
            <div class="form-group">
                <label>ตำแหน่ง *</label>
                <input type="text" name="position"
                    value="<?php echo htmlspecialchars($editDirector['position'] ?? ''); ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label>📂 หมวดหมู่ (สำคัญ) *</label>
            <select name="category" required
                style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px;">
                <option value="personnel" <?php echo (($editDirector['category'] ?? '') == 'personnel') ? 'selected' : ''; ?>>
                    บุคลากรทั่วไป (แสดงหน้าบุคลากร)</option>
                <option value="director" <?php echo (($editDirector['category'] ?? '') == 'director') ? 'selected' : ''; ?>>⭐
                    ผู้บริหารสูงสุด/ผอ. (แสดงหน้าแรก)</option>
            </select>
        </div>

        <div class="form-group">
            <label>รูปภาพ</label>
            <input type="file" name="image" accept="image/*">
        </div>

        <button type="submit"
            class="btn btn-primary"><?php echo $editDirector ? 'บันทึกแก้ไข' : 'เพิ่มข้อมูล'; ?></button>
        <?php if ($editDirector): ?><a href="?page=directors" class="btn btn-secondary">ยกเลิก</a><?php endif; ?>
    </form>
</div>

<h3>รายชื่อทั้งหมด</h3>
<table class="table">
    <thead>
        <tr>
            <th>รูป</th>
            <th>ชื่อ-ตำแหน่ง</th>
            <th>หมวดหมู่</th>
            <th>จัดการ</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($directors as $d): ?>
            <tr>
                <td>
                    <?php if ($d['image']): ?><img src="../uploads/directors/<?php echo $d['image']; ?>"
                            style="width:50px; height:50px; object-fit:cover; border-radius:50%;"><?php endif; ?>
                </td>
                <td>
                    <strong><?php echo $d['name']; ?></strong><br>
                    <small><?php echo $d['position']; ?></small>
                </td>
                <td>
                    <?php if ($d['category'] == 'director'): ?>
                        <span class="badge badge-warning">⭐ ผอ.</span>
                    <?php else: ?>
                        <span class="badge badge-info">บุคลากร</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="?page=directors&edit=<?php echo $d['id']; ?>" class="btn btn-sm btn-primary">แก้ไข</a>
                    <form method="POST" style="display:inline" onsubmit="return confirm('ลบไหม?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo $d['id']; ?>">
                        <button class="btn btn-sm btn-danger">ลบ</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>