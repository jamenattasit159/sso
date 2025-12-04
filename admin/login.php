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
    <title>เข้าสู่ระบบแอดมิน - SSO Angthong</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary: #f97316;
            --primary-dark: #ea580c;
        }

        body {
            font-family: 'Sarabun', sans-serif;
            /* ภาพพื้นหลังสวยๆ */
            background-image: url('https://images.unsplash.com/photo-1497294815431-9365093b7331?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80');
            background-size: cover;
            background-position: center;
            height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        /* Overlay สีส้มจางๆ ทับภาพพื้นหลัง */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(249, 115, 22, 0.85) 0%, rgba(194, 65, 12, 0.9) 100%);
            z-index: 1;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            /* เอฟเฟกต์กระจกเบลอ */
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 380px;
            position: relative;
            z-index: 2;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                transform: translateY(20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h2 {
            margin: 0;
            color: #333;
            font-weight: 600;
        }

        .header p {
            margin: 5px 0 0;
            color: #666;
            font-size: 0.9rem;
        }

        .input-group {
            position: relative;
            margin-bottom: 20px;
        }

        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
            transition: 0.3s;
        }

        .form-control {
            width: 100%;
            padding: 12px 12px 12px 45px;
            /* เว้นที่ด้านซ้ายให้ไอคอน */
            border: 2px solid #eee;
            border-radius: 10px;
            box-sizing: border-box;
            font-family: 'Sarabun', sans-serif;
            font-size: 1rem;
            transition: 0.3s;
            background: #f9f9f9;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            background: #fff;
        }

        .form-control:focus+i {
            color: var(--primary);
            /* เปลี่ยนสีไอคอนเมื่อโฟกัส */
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(to right, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 5px 15px rgba(249, 115, 22, 0.3);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(249, 115, 22, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .footer-links {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            font-size: 0.85rem;
        }

        .footer-links a {
            color: #666;
            text-decoration: none;
            transition: 0.3s;
        }

        .footer-links a:hover {
            color: var(--primary);
        }

        .error-msg {
            background: #fef2f2;
            border-left: 4px solid #ef4444;
            color: #b91c1c;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .system-badge {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #888;
            font-size: 0.8rem;
        }
    </style>
</head>

<body>
    <div class="login-card">
        <div class="header">
            <div style="font-size: 3rem; color: var(--primary); margin-bottom: 10px;">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <h2>เข้าสู่ระบบ</h2>
            <p>Admin Control Panel</p>
        </div>

        <?php if ($error): ?>
            <div class="error-msg">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="input-group">
                <input type="text" name="username" class="form-control" placeholder="ชื่อผู้ใช้" required autofocus>
                <i class="fa-solid fa-user"></i>
            </div>

            <div class="input-group">
                <input type="password" name="password" class="form-control" placeholder="รหัสผ่าน" required>
                <i class="fa-solid fa-lock"></i>
            </div>

            <div class="footer-links">
                <label style="display: flex; align-items: center; gap: 5px; cursor: pointer; color: #666;">
                    <input type="checkbox"> จำฉันไว้ในระบบ
                </label>
                <a href="forgot_password.php">ลืมรหัสผ่าน?</a>
            </div>

            <div style="margin-top: 25px;">
                <button type="submit" class="btn-login">
                    เข้าสู่ระบบ <i class="fa-solid fa-arrow-right" style="margin-left: 5px;"></i>
                </button>
            </div>
        </form>

        <div class="system-badge">
            <i class="fa-solid fa-building-columns"></i> ระบบความปลอดภัย SSO Angthong
        </div>
    </div>
</body>

</html>