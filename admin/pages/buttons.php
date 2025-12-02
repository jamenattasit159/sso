<?php
// admin/pages/buttons.php

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

$message = '';

// จัดการ Form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    // เพิ่มปุ่มใหม่
    if ($action == 'add') {
        $name = $_POST['name'];
        $link = $_POST['link'];
        $order = $_POST['sort_order'] ?? 0;

        if (!empty($name) && !empty($link)) {
            $stmt = $pdo->prepare("INSERT INTO sidebar_buttons (name, link, sort_order) VALUES (?, ?, ?)");
            $stmt->execute([$name, $link, $order]);
            $message = '<div class="alert alert-success">✓ เพิ่มปุ่มเรียบร้อย</div>';
        }
    }
    // ลบปุ่ม
    elseif ($action == 'delete') {
        $id = $_POST['id'];
        $pdo->prepare("DELETE FROM sidebar_buttons WHERE id = ?")->execute([$id]);
        $message = '<div class="alert alert-success">✓ ลบปุ่มเรียบร้อย</div>';
    }
    // อัปเดตลำดับ (Quick Update)
    elseif ($action == 'update_order') {
        foreach ($_POST['orders'] as $id => $val) {
            $pdo->prepare("UPDATE sidebar_buttons SET sort_order = ? WHERE id = ?")->execute([$val, $id]);
        }
        $message = '<div class="alert alert-success">✓ บันทึกลำดับเรียบร้อย</div>';
    }
}

// ดึงข้อมูล
$buttons = $pdo->query("SELECT * FROM sidebar_buttons ORDER BY sort_order ASC, created_at DESC")->fetchAll();
?>

<h2>🔗 จัดการเมนู/ปุ่มด้านข้าง</h2>
<?php echo $message; ?>

<div class="admin-form">
    <h3>เพิ่มปุ่มใหม่</h3>
    <form method="POST">
        <input type="hidden" name="action" value="add">
        <div style="display: grid; grid-template-columns: 2fr 2fr 1fr auto; gap: 10px; align-items: end;">
            <div>
                <label>ข้อความบนปุ่ม</label>
                <input type="text" name="name" placeholder="เช่น วิสัยทัศน์" required>
            </div>
            <div>
                <label>ลิ้งค์ (URL)</label>
                <input type="text" name="link" placeholder="เช่น http://... หรือ vision.php" required>
            </div>
            <div>
                <label>ลำดับ (เลขน้อยขึ้นก่อน)</label>
                <input type="number" name="sort_order" value="0">
            </div>
            <button type="submit" class="btn btn-primary">บันทึก</button>
        </div>
    </form>
</div>

<div class="card">
    <h3>รายการปุ่มปัจจุบัน</h3>
    <form method="POST">
        <input type="hidden" name="action" value="update_order">
        <table class="table">
            <thead>
                <tr>
                    <th width="10%">ลำดับ</th>
                    <th>ชื่อปุ่ม</th>
                    <th>ลิ้งค์ปลายทาง</th>
                    <th>ตัวอย่าง</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($buttons as $btn): ?>
                    <tr>
                        <td>
                            <input type="number" name="orders[<?php echo $btn['id']; ?>]"
                                value="<?php echo $btn['sort_order']; ?>" style="width: 60px; text-align: center;">
                        </td>
                        <td><?php echo htmlspecialchars($btn['name']); ?></td>
                        <td style="font-size: 12px; color: #666;"><?php echo htmlspecialchars($btn['link']); ?></td>
                        <td>
                            <a href="#" class="btn btn-sm btn-secondary" style="pointer-events: none;">
                                <?php echo htmlspecialchars($btn['name']); ?>
                            </a>
                        </td>
                        <td>
                            <button type="submit" form="delete-form-<?php echo $btn['id']; ?>" class="btn btn-danger btn-sm"
                                onclick="return confirm('ลบหรือไม่?')">ลบ</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div style="margin-top: 15px; text-align: right;">
            <button type="submit" class="btn btn-primary">💾 บันทึกลำดับทั้งหมด</button>
        </div>
    </form>

    <?php foreach ($buttons as $btn): ?>
        <form id="delete-form-<?php echo $btn['id']; ?>" method="POST" style="display:none;">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?php echo $btn['id']; ?>">
        </form>
    <?php endforeach; ?>
</div>