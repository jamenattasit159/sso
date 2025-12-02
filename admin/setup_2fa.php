<?php
session_start();
require '../config.php';
require '../vendor/autoload.php';

// ตรวจสอบสิทธิ์
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

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$message = '';

// --- ส่วนที่ 1: บันทึกข้อมูล ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['enable_2fa'])) {
    $secret = $_POST['secret'];
    $code = trim($_POST['code']);

    if ($g->checkCode($secret, $code)) {
        $pdo->prepare("UPDATE admin_users SET google_2fa_secret = ? WHERE id = ?")->execute([$secret, $userId]);

        if (isset($_SESSION['temp_admin_id'])) {
            $_SESSION['admin_id'] = $userId;
            $_SESSION['username'] = $user['username'];
            unset($_SESSION['temp_admin_id']);
            header('Location: index.php');
            exit;
        }

        $message = '<div class="alert alert-success">✅ เปิดใช้งาน 2FA สำเร็จเรียบร้อย!</div>';
        echo "<script>setTimeout(function(){ window.location.href = 'index.php'; }, 1500);</script>";
    } else {
        $message = '<div class="alert alert-error">❌ รหัสไม่ถูกต้อง กรุณาลองใหม่</div>';
    }
}

// --- ส่วนที่ 2: เตรียมข้อมูล ---
$secret = $user['google_2fa_secret'];
$is_already_active = !empty($secret);
$display_secret = $is_already_active ? $secret : $g->generateSecret();

// สร้าง Text สำหรับ QR Code (ไม่ใช่ URL รูปภาพแล้ว แต่เป็น Text ข้อมูล)
$authUrl = "otpauth://totp/SSO Angthong:{$user['email']}?secret={$display_secret}&issuer=SSO Angthong";
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งค่า Google Authenticator</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        body {
            background-color: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            font-family: 'Sarabun', sans-serif;
        }

        .setup-container {
            background: white;
            width: 100%;
            max-width: 480px;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .step-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
        }

        /* กล่องใส่ QR Code */
        #qrcode {
            width: 200px;
            height: 200px;
            margin: 20px auto;
            border: 5px solid white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            display: flex;
            /* จัดกึ่งกลาง */
            justify-content: center;
            align-items: center;
        }

        #qrcode img {
            margin: 0 auto;
            /* จัดกึ่งกลางรูปที่ JS สร้างมา */
        }

        .secret-code {
            font-family: monospace;
            background: #e2e8f0;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 16px;
            letter-spacing: 1px;
            color: #334155;
        }

        .otp-input {
            width: 200px;
            padding: 12px;
            font-size: 24px;
            text-align: center;
            letter-spacing: 5px;
            border: 2px solid #cbd5e1;
            border-radius: 8px;
            margin: 10px 0 20px;
            outline: none;
            transition: all 0.2s;
        }

        .otp-input:focus {
            border-color: #f97316;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
        }

        .btn-submit {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(249, 115, 22, 0.3);
        }

        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
    </style>
</head>

<body>

    <div class="setup-container">
        <h2 style="color: #334155; margin-bottom: 10px;">🔐 ตั้งค่าความปลอดภัย 2 ขั้นตอน</h2>
        <p style="color: #64748b; font-size: 14px; margin-bottom: 20px;">
            บัญชีของคุณ: <strong><?php echo htmlspecialchars($user['email']); ?></strong>
        </p>

        <?php echo $message; ?>

        <?php if ($is_already_active && !isset($_POST['enable_2fa'])): ?>
            <div style="padding: 40px 0;">
                <div style="font-size: 60px; margin-bottom: 20px;">✅</div>
                <h3 style="color: #166534;">คุณเปิดใช้งาน 2FA เรียบร้อยแล้ว</h3>
                <a href="index.php" class="btn-submit"
                    style="text-decoration: none; display: inline-block;">เข้าสู่หน้าหลัก</a>
            </div>
        <?php else: ?>
            <div class="step-box">
                <div style="text-align: left; margin-bottom: 10px; font-weight: 600; color: #334155;">
                    1️⃣ สแกน QR Code
                </div>
                <p style="font-size: 13px; color: #64748b; margin: 0;">
                    เปิดแอป Google Authenticator แล้วสแกนรูปด้านล่าง
                </p>

                <div id="qrcode"></div>

                <div style="font-size: 12px; color: #64748b; margin-top: 10px;">
                    หรือป้อนคีย์ด้วยตนเอง: <span class="secret-code"><?php echo $display_secret; ?></span>
                </div>
            </div>

            <form method="POST">
                <input type="hidden" name="secret" value="<?php echo $display_secret; ?>">

                <div class="step-box" style="margin-bottom: 30px;">
                    <div style="text-align: left; margin-bottom: 10px; font-weight: 600; color: #334155;">
                        2️⃣ ยืนยันรหัส
                    </div>
                    <p style="font-size: 13px; color: #64748b; margin-bottom: 10px;">
                        นำรหัส 6 หลักจากแอปมากรอกเพื่อยืนยัน
                    </p>
                    <input type="text" name="code" class="otp-input" placeholder="000 000" maxlength="6" inputmode="numeric"
                        required autofocus autocomplete="off">
                </div>

                <button type="submit" name="enable_2fa" class="btn-submit">ยืนยันและเปิดใช้งาน</button>
            </form>

            <script>
                // สร้าง QR Code เมื่อโหลดหน้าเว็บ
                new QRCode(document.getElementById("qrcode"), {
                    text: "<?php echo $authUrl; ?>", // ข้อมูลสำหรับ App Authenticator
                    width: 180,
                    height: 180,
                    colorDark: "#000000",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.H
                });
            </script>
        <?php endif; ?>
    </div>

</body>

</html>