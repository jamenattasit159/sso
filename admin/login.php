<?php
session_start();
require '../config.php';

// ถ้าล็อกอินอยู่แล้ว ดีดไปหน้าแรก
if (isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน';
    } else {
        try {
            // 1. ดึงข้อมูล User จาก Username
            $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ? AND status = 'active'");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            // 2. ตรวจสอบรหัสผ่าน
            if ($user && password_verify($password, $user['password'])) {

                // สร้าง Session ชั่วคราว (ยังไม่ถือว่าล็อกอินสมบูรณ์)
                $_SESSION['temp_admin_id'] = $user['id'];

                if (!empty($user['google_2fa_secret'])) {
                    // CASE A: เคยตั้งค่าแล้ว -> ไปหน้ากรอกรหัส 6 หลัก
                    header('Location: verify_2fa.php');
                } else {
                    // CASE B: ยังไม่เคยตั้งค่า -> บังคับไปหน้าสแกน QR Code เดี๋ยวนี้!
                    header('Location: setup_2fa.php');
                }
                exit;

            } else {
                $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
            }
        } catch (Exception $e) {
            $error = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบแอดมิน</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }

        .login-box {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
        }

        .login-box h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            box-sizing: border-box;
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            background: #f97316;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-login:hover {
            background: #c2410c;
        }

        .error-msg {
            background: #fee2e2;
            color: #ef4444;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 14px;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="login-box">
        <h2>🔐 เข้าสู่ระบบ</h2>

        <?php if ($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="text" name="username" class="form-control" placeholder="ชื่อผู้ใช้" required autofocus>
            <input type="password" name="password" class="form-control" placeholder="รหัสผ่าน" required>
            <div style="text-align: right; margin-top: 5px;">
                <a href="forgot_password.php"
                    style="font-size: 12px; color: #667eea; text-decoration: none;">ลืมรหัสผ่าน?</a>
            </div>
            <button type="submit" class="btn-login">เข้าสู่ระบบ</button>
        </form>

        <div style="margin-top: 20px; text-align: center; font-size: 12px; color: #666;">
            ระบบความปลอดภัย SSO Angthong
        </div>
       
    </div>
</body>

</html>