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

        // กรณีอัปเดตจากหน้า Profile (ถ้ามี)
        $message = '<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> เปิดใช้งาน 2FA สำเร็จเรียบร้อย!</div>';
        echo "<script>setTimeout(function(){ window.location.href = 'index.php'; }, 1500);</script>";

        // อัปเดตตัวแปรเพื่อให้หน้าเว็บเปลี่ยนสถานะทันทีโดยไม่ต้องรีโหลด
        $user['google_2fa_secret'] = $secret;
    } else {
        $message = '<div class="alert alert-error"><i class="fa-solid fa-circle-xmark"></i> รหัสไม่ถูกต้อง กรุณาลองใหม่</div>';
    }
}

// --- ส่วนที่ 2: เตรียมข้อมูล ---
$secret = $user['google_2fa_secret'];
$is_already_active = !empty($secret);
// ถ้ามี Secret แล้ว ให้ใช้ตัวเดิม แต่ถ้าไม่มีให้เจนใหม่
$display_secret = $is_already_active ? $secret : $g->generateSecret();

// สร้าง Text สำหรับ QR Code
$authUrl = "otpauth://totp/SSO Angthong:{$user['email']}?secret={$display_secret}&issuer=SSO Angthong";
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งค่า 2FA - SSO Angthong</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <style>
        :root {
            --primary: #f97316;
            --primary-dark: #ea580c;
            --success: #10b981;
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
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 450px;
            position: relative;
            z-index: 2;
            text-align: center;
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        h2 {
            color: #333;
            margin: 0 0 10px 0;
            font-weight: 600;
        }

        .user-email {
            background: #f1f5f9;
            color: #64748b;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            display: inline-block;
            margin-bottom: 25px;
        }

        /* Styles for Setup Mode */
        .step-container {
            text-align: left;
            margin-bottom: 20px;
        }

        .step-label {
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .qr-frame {
            background: white;
            padding: 15px;
            border-radius: 12px;
            border: 2px dashed #cbd5e1;
            display: inline-block;
            margin: 10px 0 20px 0;
            transition: 0.3s;
        }

        .qr-frame:hover {
            border-color: var(--primary);
            transform: scale(1.02);
        }

        #qrcode img {
            margin: 0 auto;
        }

        .manual-key {
            font-size: 0.85rem;
            color: #64748b;
            background: #fff;
            padding: 8px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            word-break: break-all;
            margin-top: 5px;
        }

        .otp-input {
            width: 100%;
            padding: 15px;
            font-size: 1.5rem;
            text-align: center;
            letter-spacing: 8px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            margin-bottom: 20px;
            outline: none;
            transition: 0.3s;
            box-sizing: border-box;
            background: #f8fafc;
            font-family: monospace;
        }

        .otp-input:focus {
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.1);
        }

        .btn-action {
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

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(249, 115, 22, 0.4);
        }

        /* Styles for Active Mode */
        .active-state {
            padding: 30px 0;
        }

        .success-icon {
            font-size: 5rem;
            color: var(--success);
            margin-bottom: 20px;
            animation: popIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        @keyframes popIn {
            0% {
                transform: scale(0);
                opacity: 0;
            }

            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        /* Alerts */
        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            text-align: left;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border-left: 4px solid #166534;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #991b1b;
        }

        .footer-note {
            margin-top: 25px;
            font-size: 0.8rem;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 15px;
        }
    </style>
</head>

<body>

    <div class="glass-card">
        <div style="font-size: 2.5rem; color: var(--primary); margin-bottom: 15px;">
            <i class="fa-solid fa-mobile-screen-button"></i>
        </div>

        <h2>Google Authenticator</h2>
        <div class="user-email">
            <i class="fa-solid fa-user-circle"></i> <?php echo htmlspecialchars($user['email']); ?>
        </div>

        <?php echo $message; ?>

        <?php if ($is_already_active): ?>
            <div class="active-state">
                <div class="success-icon">
                    <i class="fa-solid fa-shield-check"></i>
                </div>
                <h3 style="color: #333; margin-bottom: 10px;">ความปลอดภัยขั้นสูงทำงานอยู่</h3>
                <p style="color: #666; font-size: 0.95rem; margin-bottom: 30px;">
                    บัญชีของคุณได้รับการปกป้องด้วยการยืนยันตัวตนสองขั้นตอนเรียบร้อยแล้ว
                </p>
                <a href="index.php" class="btn-action"
                    style="text-decoration: none; display: inline-block; box-sizing: border-box;">
                    <i class="fa-solid fa-house"></i> กลับสู่หน้าหลัก
                </a>
            </div>

        <?php else: ?>
            <div style="text-align: center;">
                <p style="color: #666; margin-bottom: 20px; font-size: 0.95rem;">
                    เพื่อความปลอดภัย กรุณาสแกน QR Code ด้วยแอป Google Authenticator
                </p>

                <div class="qr-frame">
                    <div id="qrcode"></div>
                </div>

                <div style="margin-bottom: 25px;">
                    <button type="button" onclick="toggleManual()"
                        style="background:none; border:none; color:#666; cursor:pointer; text-decoration:underline; font-size:0.85rem;">
                        สแกนไม่ได้? ดูรหัส Setup Key
                    </button>
                    <div id="manual-key-box" style="display:none;" class="manual-key">
                        KEY: <strong><?php echo $display_secret; ?></strong>
                    </div>
                </div>

                <form method="POST">
                    <input type="hidden" name="secret" value="<?php echo $display_secret; ?>">

                    <div style="text-align: left; margin-bottom: 8px; font-size: 0.9rem; font-weight: 600; color: #333;">
                        กรอกรหัส 6 หลักจากแอป
                    </div>
                    <input type="text" name="code" class="otp-input" placeholder="000 000" maxlength="6" inputmode="numeric"
                        autocomplete="one-time-code" required autofocus>

                    <button type="submit" name="enable_2fa" class="btn-action">
                        ยืนยันและเปิดใช้งาน <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </form>
            </div>

            <script>
                // Generate QR Code
                new QRCode(document.getElementById("qrcode"), {
                    text: "<?php echo $authUrl; ?>",
                    width: 160,
                    height: 160,
                    colorDark: "#000000",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.H
                });

                function toggleManual() {
                    var x = document.getElementById("manual-key-box");
                    if (x.style.display === "none") {
                        x.style.display = "block";
                    } else {
                        x.style.display = "none";
                    }
                }
            </script>
        <?php endif; ?>

        <div class="footer-note">
            SSO Angthong Security System
        </div>
    </div>

</body>

</html>