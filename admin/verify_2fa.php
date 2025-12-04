<?php
session_start();
require '../config.php';
require '../vendor/autoload.php';

// เช็คว่าผ่านการล็อกอินขั้นแรกมาหรือยัง (ต้องมี temp_admin_id)
if (!isset($_SESSION['temp_admin_id'])) {
    header('Location: login.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $code = trim($_POST['code'] ?? '');

    if (empty($code)) {
        $error = 'กรุณากรอกรหัส 6 หลัก';
    } else {
        try {
            // ดึงข้อมูล User และ Secret จาก Database
            $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE id = ?");
            $stmt->execute([$_SESSION['temp_admin_id']]);
            $user = $stmt->fetch();

            if ($user && !empty($user['google_2fa_secret'])) {
                $g = new \Google\Authenticator\GoogleAuthenticator();

                // ตรวจสอบรหัส (อนุญาตให้เวลาคลาดเคลื่อนได้เล็กน้อย)
                if ($g->checkCode($user['google_2fa_secret'], $code)) {

                    // --- ผ่าน! เลื่อนขั้นจาก Temp เป็น Admin เต็มตัว ---
                    $_SESSION['admin_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role']; // ถ้ามี role

                    // ล้าง Session ชั่วคราว
                    unset($_SESSION['temp_admin_id']);

                    // เข้าสู่ระบบสำเร็จ -> ไปหน้าหลัก
                    header('Location: index.php');
                    exit;

                } else {
                    $error = 'รหัสไม่ถูกต้อง หรือหมดอายุแล้ว';
                }
            } else {
                // กรณี User นี้ไม่มี Secret (ผิดปกติ ถ้าเข้ามาหน้านี้ได้)
                header('Location: login.php');
                exit;
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
    <title>ยืนยันตัวตน 2FA - SSO Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary: #f97316;
            --primary-dark: #ea580c;
        }

        body {
            font-family: 'Sarabun', sans-serif;
            background-image: url('https://images.unsplash.com/photo-1497294815431-9365093b7331?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80');
            background-size: cover;
            background-position: center;
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        /* Overlay */
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

        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            padding: 40px 30px;
            border-radius: 20px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            position: relative;
            z-index: 2;
            text-align: center;
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

        .icon-header {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 20px;
            background: #fff3e0;
            width: 80px;
            height: 80px;
            line-height: 80px;
            border-radius: 50%;
            display: inline-block;
        }

        h2 {
            margin: 0;
            color: #333;
            font-weight: 600;
        }

        p {
            color: #666;
            font-size: 0.95rem;
            margin: 10px 0 25px;
        }

        .otp-input {
            width: 100%;
            padding: 15px;
            font-size: 1.8rem;
            text-align: center;
            letter-spacing: 10px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            margin-bottom: 25px;
            outline: none;
            transition: 0.3s;
            box-sizing: border-box;
            background: #f8fafc;
            font-family: monospace;
            font-weight: bold;
            color: #333;
        }

        .otp-input:focus {
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.1);
        }

        .otp-input::placeholder {
            color: #cbd5e1;
            letter-spacing: 2px;
            font-size: 1rem;
            font-family: 'Sarabun', sans-serif;
            font-weight: normal;
        }

        .btn-verify {
            width: 100%;
            padding: 14px;
            background: linear-gradient(to right, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: 0.2s;
            box-shadow: 0 4px 12px rgba(249, 115, 22, 0.3);
        }

        .btn-verify:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(249, 115, 22, 0.4);
        }

        .error-msg {
            background: #fef2f2;
            color: #b91c1c;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: 1px solid #fecaca;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #94a3b8;
            text-decoration: none;
            font-size: 0.9rem;
            transition: 0.3s;
        }

        .back-link:hover {
            color: var(--primary);
        }
    </style>
</head>

<body>

    <div class="glass-card">
        <div class="icon-header">
            <i class="fa-solid fa-shield-halved"></i>
        </div>

        <h2>ยืนยันตัวตน 2FA</h2>
        <p>กรุณากรอกรหัส 6 หลักจากแอป Google Authenticator</p>

        <?php if ($error): ?>
            <div class="error-msg">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="text" name="code" class="otp-input" placeholder="กรอกรหัส 6 หลัก" maxlength="6"
                inputmode="numeric" autocomplete="one-time-code" required autofocus>

            <button type="submit" class="btn-verify">
                ยืนยันเพื่อเข้าสู่ระบบ <i class="fa-solid fa-arrow-right" style="margin-left: 5px;"></i>
            </button>
        </form>

        <a href="login.php" class="back-link">
            <i class="fa-solid fa-arrow-left"></i> กลับไปหน้าล็อกอิน
        </a>
    </div>

</body>

</html>