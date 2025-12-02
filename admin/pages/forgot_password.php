<?php
session_start();
require '../config.php';

$step = 1; // 1=ตรวจสอบข้อมูล, 2=ตั้งรหัสใหม่
$message = '';
$user_id_reset = 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    // ขั้นตอนที่ 1: ตรวจสอบข้อมูล
    if ($action == 'verify') {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);

        $stmt = $pdo->prepare("SELECT id FROM admin_users WHERE username = ? AND email = ? AND status = 'active'");
        $stmt->execute([$username, $email]);
        $user = $stmt->fetch();

        if ($user) {
            $step = 2;
            $user_id_reset = $user['id'];
        } else {
            $message = '<div class="alert alert-error">❌ ไม่พบข้อมูลผู้ใช้ หรืออีเมลไม่ถูกต้อง</div>';
        }
    }
    // ขั้นตอนที่ 2: เปลี่ยนรหัสผ่าน
    elseif ($action == 'reset') {
        $new_pass = $_POST['new_password'];
        $confirm_pass = $_POST['confirm_password'];
        $uid = $_POST['uid'];

        if (strlen($new_pass) < 4) {
            $message = '<div class="alert alert-error">❌ รหัสผ่านต้องมีความยาวอย่างน้อย 4 ตัวอักษร</div>';
            $step = 2;
            $user_id_reset = $uid;
        } elseif ($new_pass !== $confirm_pass) {
            $message = '<div class="alert alert-error">❌ รหัสผ่านไม่ตรงกัน</div>';
            $step = 2;
            $user_id_reset = $uid;
        } else {
            $hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
            if ($stmt->execute([$hash, $uid])) {
                $message = '<div class="alert alert-success">✅ เปลี่ยนรหัสผ่านสำเร็จ! <a href="login.php">เข้าสู่ระบบ</a></div>';
                $step = 3; // เสร็จสิ้น
            } else {
                $message = '<div class="alert alert-error">❌ เกิดข้อผิดพลาด</div>';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลืมรหัสผ่าน</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        body {
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            font-family: 'Sarabun', sans-serif;
        }

        .card {
            width: 100%;
            max-width: 400px;
            padding: 30px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
        }

        .btn-full {
            width: 100%;
            margin-top: 10px;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #666;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="card">
        <h2 style="text-align: center; margin-bottom: 20px; color: #334155;">🔑 กู้คืนรหัสผ่าน</h2>
        <?php echo $message; ?>

        <?php if ($step == 1): ?>
            <form method="POST">
                <input type="hidden" name="action" value="verify">
                <div class="form-group">
                    <label>Username (ชื่อผู้ใช้)</label>
                    <input type="text" name="username" required placeholder="กรอกชื่อผู้ใช้ของคุณ">
                </div>
                <div class="form-group">
                    <label>Email (ที่ลงทะเบียนไว้)</label>
                    <input type="email" name="email" required placeholder="name@example.com">
                </div>
                <button type="submit" class="btn btn-primary btn-full">ตรวจสอบข้อมูล</button>
            </form>
        <?php elseif ($step == 2): ?>
            <form method="POST">
                <input type="hidden" name="action" value="reset">
                <input type="hidden" name="uid" value="<?php echo $user_id_reset; ?>">
                <div class="alert" style="background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd;">✓
                    ยืนยันตัวตนถูกต้อง ตั้งรหัสใหม่ได้เลย</div>
                <div class="form-group">
                    <label>รหัสผ่านใหม่</label>
                    <input type="password" name="new_password" required placeholder="********">
                </div>
                <div class="form-group">
                    <label>ยืนยันรหัสผ่านใหม่</label>
                    <input type="password" name="confirm_password" required placeholder="********">
                </div>
                <button type="submit" class="btn btn-success btn-full">เปลี่ยนรหัสผ่าน</button>
            </form>
        <?php endif; ?>

        <a href="login.php" class="back-link">← กลับไปหน้าเข้าสู่ระบบ</a>
    </div>
</body>

</html>