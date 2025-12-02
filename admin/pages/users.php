<?php
// admin/pages/users.php

// ตรวจสอบสิทธิ์: ต้องเป็น Super Admin เท่านั้น
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
    echo '<div class="alert alert-danger">⛔ <strong>Access Denied:</strong> คุณไม่มีสิทธิ์เข้าถึงหน้านี้ (เฉพาะ Super Admin)</div>';
    return;
}

$message = '';

// จัดการ Form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. เพิ่มผู้ใช้ใหม่
    if ($action == 'add') {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $email = trim($_POST['email']);
        $role = $_POST['role'];

        // เช็คว่า username ซ้ำไหม
        $check = $pdo->prepare("SELECT id FROM admin_users WHERE username = ?");
        $check->execute([$username]);

        if ($check->rowCount() > 0) {
            $message = '<div class="alert alert-danger">❌ ชื่อผู้ใช้นี้มีอยู่แล้ว</div>';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO admin_users (username, password, email, role, status, created_at) VALUES (?, ?, ?, ?, 'active', NOW())");

            if ($stmt->execute([$username, $hash, $email, $role])) {
                $message = '<div class="alert alert-success">✓ เพิ่มผู้ใช้เรียบร้อย</div>';
            } else {
                $message = '<div class="alert alert-danger">❌ เกิดข้อผิดพลาด</div>';
            }
        }
    }

    // 2. ลบผู้ใช้
    elseif ($action == 'delete') {
        $id = $_POST['id'];
        if ($id == $_SESSION['admin_id']) {
            $message = '<div class="alert alert-danger">❌ ไม่สามารถลบบัญชีตัวเองได้</div>';
        } else {
            $pdo->prepare("DELETE FROM admin_users WHERE id = ?")->execute([$id]);
            $message = '<div class="alert alert-success">✓ ลบผู้ใช้เรียบร้อย</div>';
        }
    }

    // 3. แก้ไขข้อมูล / เปลี่ยนรหัสผ่าน (Reset Password)
    elseif ($action == 'edit') {
        $id = $_POST['id'];
        $email = trim($_POST['email']);
        $role = $_POST['role'];
        $new_pass = $_POST['password']; // รับค่ารหัสผ่านใหม่

        // กรณีเปลี่ยนรหัสผ่านด้วย
        if (!empty($new_pass)) {
            $hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $sql = "UPDATE admin_users SET email = ?, role = ?, password = ? WHERE id = ?";
            $params = [$email, $role, $hash, $id];
            $msg_text = "อัปเดตข้อมูลและเปลี่ยนรหัสผ่านเรียบร้อย";
        }
        // กรณีไม่เปลี่ยนรหัสผ่าน (อัปเดตแค่ข้อมูล)
        else {
            $sql = "UPDATE admin_users SET email = ?, role = ? WHERE id = ?";
            $params = [$email, $role, $id];
            $msg_text = "อัปเดตข้อมูลเรียบร้อย";
        }

        if ($pdo->prepare($sql)->execute($params)) {
            $message = '<div class="alert alert-success">✓ ' . $msg_text . '</div>';
        } else {
            $message = '<div class="alert alert-danger">❌ เกิดข้อผิดพลาดในการบันทึก</div>';
        }
    }
}

// ดึงข้อมูลผู้ใช้ทั้งหมด
$users = $pdo->query("SELECT * FROM admin_users ORDER BY created_at DESC")->fetchAll();
?>

<h2>👥 จัดการผู้ใช้งาน (Super Admin)</h2>
<?php echo $message; ?>

<div class="admin-form">
    <h3><i class="fas fa-user-plus"></i> เพิ่มผู้ดูแลระบบใหม่</h3>
    <form method="POST">
        <input type="hidden" name="action" value="add">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required placeholder="ชื่อเข้าระบบ (ภาษาอังกฤษ)"
                    pattern="[a-zA-Z0-9_]+" title="ภาษาอังกฤษและตัวเลขเท่านั้น">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required placeholder="รหัสผ่าน">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required placeholder="admin@example.com">
            </div>
            <div class="form-group">
                <label>สิทธิ์การใช้งาน (Role)</label>
                <select name="role">
                    <option value="admin">Admin ทั่วไป (จัดการเนื้อหา)</option>
                    <option value="super_admin">Super Admin (จัดการทุกอย่าง)</option>
                </select>
            </div>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> บันทึกผู้ใช้</button>
    </form>
</div>

<div class="card">
    <h3>📋 รายชื่อผู้ดูแลระบบทั้งหมด</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Username</th>
                <th>Email</th>
                <th>สิทธิ์ (Role)</th>
                <th>2FA</th>
                <th>จัดการ</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td>
                        <div style="font-weight: bold; color: var(--text-main);">
                            <?php echo htmlspecialchars($u['username']); ?>
                            <?php if ($u['id'] == $_SESSION['admin_id'])
                                echo ' <span class="badge badge-success">(คุณ)</span>'; ?>
                        </div>
                    </td>
                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                    <td>
                        <?php if ($u['role'] == 'super_admin'): ?>
                            <span style="color: #d97706; font-weight: bold;">👑 Super Admin</span>
                        <?php else: ?>
                            <span style="color: #64748b;">👤 Admin</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo !empty($u['google_2fa_secret']) ? '<span style="color:green">✅ เปิด</span>' : '<span style="color:#ccc">ปิด</span>'; ?>
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-secondary"
                            onclick="toggleEdit('<?php echo $u['id']; ?>')">
                            <i class="fas fa-edit"></i> แก้ไข/เปลี่ยนรหัส
                        </button>

                        <?php if ($u['id'] != $_SESSION['admin_id']): ?>
                            <form method="POST" style="display:inline;"
                                onsubmit="return confirm('⚠️ ยืนยันการลบผู้ใช้ <?php echo $u['username']; ?> ?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>

                <tr id="edit-row-<?php echo $u['id']; ?>" style="display:none; background: #fff7ed;">
                    <td colspan="5" style="padding: 20px; border-left: 4px solid #f97316;">
                        <form method="POST">
                            <input type="hidden" name="action" value="edit">
                            <input type="hidden" name="id" value="<?php echo $u['id']; ?>">

                            <div style="font-weight: bold; margin-bottom: 10px; color: #c2410c;">
                                ✏️ แก้ไขข้อมูล: <?php echo htmlspecialchars($u['username']); ?>
                            </div>

                            <div
                                style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px;">
                                <div>
                                    <label style="font-size: 12px; display: block; margin-bottom: 5px;">อีเมล</label>
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($u['email']); ?>"
                                        required style="background: white;">
                                </div>
                                <div>
                                    <label style="font-size: 12px; display: block; margin-bottom: 5px; color: #d97706;">🔑
                                        ตั้งรหัสผ่านใหม่ (ว่าง = ไม่เปลี่ยน)</label>
                                    <input type="password" name="password" placeholder="ระบุรหัสผ่านใหม่ที่นี่..."
                                        style="background: white; border-color: #fdba74;">
                                </div>
                                <div>
                                    <label
                                        style="font-size: 12px; display: block; margin-bottom: 5px;">สิทธิ์การใช้งาน</label>
                                    <select name="role" style="background: white;">
                                        <option value="admin" <?php echo $u['role'] == 'admin' ? 'selected' : ''; ?>>Admin
                                        </option>
                                        <option value="super_admin" <?php echo $u['role'] == 'super_admin' ? 'selected' : ''; ?>>
                                            Super Admin</option>
                                    </select>
                                </div>
                            </div>

                            <div style="text-align: right;">
                                <button type="button" class="btn btn-sm btn-secondary"
                                    onclick="toggleEdit('<?php echo $u['id']; ?>')">ยกเลิก</button>
                                <button type="submit" class="btn btn-sm btn-primary">บันทึกการเปลี่ยนแปลง</button>
                            </div>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
    function toggleEdit(id) {
        var row = document.getElementById('edit-row-' + id);
        if (row.style.display === 'none') {
            row.style.display = 'table-row';
        } else {
            row.style.display = 'none';
        }
    }
</script>