<?php
session_start();
require '../config.php';
require '../vendor/autoload.php';

// ตรวจสอบสิทธิ์: ต้องมีอย่างใดอย่างหนึ่ง (Login แล้ว หรือ ติดสถานะ Temp)
$userId = $_SESSION['admin_id'] ?? $_SESSION['temp_admin_id'] ?? null;

if (!$userId) {
    header('Location: login.php');
    exit;
}

$g = new \Google\Authenticator\GoogleAuthenticator();

// ดึงข้อมูล User
$stmt = $pdo->prepare("SELECT * FROM admin_users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$message = '';

// 1. กดบันทึกการตั้งค่า
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['enable_2fa'])) {
    $secret = $_POST['secret'];
    $code = $_POST['code'];

    if ($g->checkCode($secret, $code)) {
        // บันทึก Secret
        $pdo->prepare("UPDATE admin_users SET google_2fa_secret = ? WHERE id = ?")->execute([$secret, $userId]);

        // *** จุดสำคัญ: ถ้ามาจาก Temp Session ให้เปลี่ยนเป็น Login จริงเลย ***
        if (isset($_SESSION['temp_admin_id'])) {
            $_SESSION['admin_id'] = $userId;
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            unset($_SESSION['temp_admin_id']);

            // ส่งเข้าหน้าหลักทันที
            header('Location: index.php');
            exit;
        }

        $message = '<div class="alert alert-success">✅ เปิดใช้งาน 2FA สำเร็จ!</div>';
        header("Refresh:1"); // รีเฟรชหน้า
    } else {
        $message = '<div class="alert alert-error">❌ รหัสไม่ถูกต้อง ลองใหม่ครับ</div>';
    }
}

// 2. สร้าง Secret ใหม่ถ้ายังไม่มี
$secret = $user['google_2fa_secret'];
if (empty($secret)) {
    $secret = $g->generateSecret();
}

// สร้างลิ้งค์ QR
$qrUrl = \Google\Authenticator\GoogleQrUrl::generate($user['email'], $secret, 'SSO Admin');
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>ตั้งค่า Google 2FA</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .setup-box {
            max-width: 500px;
            margin: 50px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .qr-img {
            margin: 20px 0;
            border: 5px solid #f3f4f6;
            border-radius: 10px;
        }

        .code-input {
            font-size: 24px;
            letter-spacing: 5px;
            text-align: center;
            width: 200px;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 8px;
        }
    </style>
</head>

<body>
    <div class="setup-box">
        <h2 style="color: #f97316;">🔐 ตั้งค่าความปลอดภัย (2FA)</h2>
        <p style="color: #666;">เพื่อความปลอดภัย บัญชีนี้ต้องเปิดใช้งาน 2FA ก่อนเข้าใช้งาน</p>

        <?php echo $message; ?>

        <div style="margin: 20px 0;">
            1. โหลดแอป <strong>Google Authenticator</strong><br>
            2. สแกน QR Code นี้
            <br>
            <img src="https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=<?php echo urlencode($qrUrl); ?>"
                class="qr-img">
            <br>
            <small style="color: #999;">หรือป้อนคีย์: <code><?php echo $secret; ?></code></small>
        </div>

        <form method="POST">
            <input type="hidden" name="secret" value="<?php echo $secret; ?>">
            <p>3. กรอกรหัส 6 หลักจากแอป</p>
            <input type="text" name="code" class="code-input" placeholder="000 000" required autofocus>
            <br><br>
            <button type="submit" name="enable_2fa" class="btn btn-primary">ยืนยันและเปิดใช้งาน</button>
        </form>
    </div>
</body>

</html>