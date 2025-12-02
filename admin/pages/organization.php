<?php
// admin/pages/organization.php

// ตรวจสอบ Session
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

$message = '';

// จัดการการบันทึกข้อมูล
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action == 'update_organization') {
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $vision = $_POST['vision'] ?? '';
        $mission = $_POST['mission'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $email = $_POST['email'] ?? '';
        $address = $_POST['address'] ?? '';

        // ค่าเริ่มต้นเอามาจาก Input hidden (ซึ่งจะเปลี่ยนเมื่อเลือก emoji หรืออัปโหลด)
        $icon = $_POST['icon'] ?? '🏥';

        // --- ส่วนจัดการอัปโหลดโลโก้ (เพิ่มรองรับ .ico) ---
        if (isset($_FILES['custom_logo']) && $_FILES['custom_logo']['error'] == 0) {
            // เพิ่ม 'ico' ในรายการที่อนุญาต
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'ico'];
            $filename = $_FILES['custom_logo']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (in_array($ext, $allowed)) {
                // สร้างโฟลเดอร์ถ้ายังไม่มี
                $uploadDir = '../uploads/logos/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                // สร้างชื่อไฟล์ใหม่
                $newFilename = 'logo_' . time() . '.' . $ext;
                $uploadPath = $uploadDir . $newFilename;

                if (move_uploaded_file($_FILES['custom_logo']['tmp_name'], $uploadPath)) {
                    // ถ้าอัปโหลดสำเร็จ ใช้ path นี้เป็น icon
                    $icon = 'uploads/logos/' . $newFilename;
                }
            } else {
                $message = '<div class="alert alert-error">❌ ไฟล์รองรับเฉพาะรูปภาพ (JPG, PNG, GIF, WEBP, ICO) เท่านั้น</div>';
            }
        }
        // ------------------------------

        if (empty($message)) {
            try {
                $stmt = $pdo->prepare(
                    "UPDATE organization_info SET name=?, description=?, vision=?, mission=?, phone=?, email=?, address=?, logo=?, updated_at=NOW() WHERE id=1"
                );
                $stmt->execute([$name, $description, $vision, $mission, $phone, $email, $address, $icon]);
                $message = '<div class="alert alert-success">✓ บันทึกข้อมูลหน่วยงานเรียบร้อยแล้ว</div>';
            } catch (Exception $e) {
                $message = '<div class="alert alert-error">❌ เกิดข้อผิดพลาด: ' . $e->getMessage() . '</div>';
            }
        }
    }
}

// ดึงข้อมูลหน่วยงานปัจจุบัน
$orgInfo = $pdo->query("SELECT * FROM organization_info LIMIT 1")->fetch();

if (!$orgInfo) {
    $pdo->query("INSERT INTO organization_info (id, name, logo) VALUES (1, 'สถาบันอุตสาหกรรมสุขภาพ', '🏥')");
    $orgInfo = $pdo->query("SELECT * FROM organization_info LIMIT 1")->fetch();
}

$currentLogo = $orgInfo['logo'] ?? '🏥';
$isCustomLogo = strpos($currentLogo, 'uploads/') !== false;
?>

<style>
    .icon-section-wrapper {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        align-items: flex-start;
    }

    .icon-selector {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        flex: 1;
    }

    .icon-option {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 50px;
        height: 50px;
        border: 2px solid #ddd;
        border-radius: 8px;
        font-size: 24px;
        cursor: pointer;
        transition: all 0.2s;
        background: #fff;
        position: relative;
        overflow: hidden;
    }

    .icon-option:hover {
        border-color: #667eea;
        background: #eef2f8;
        transform: scale(1.05);
    }

    .icon-option.active {
        border-color: #667eea;
        background: #667eea;
        color: white;
        box-shadow: 0 4px 6px rgba(102, 126, 234, 0.3);
    }

    /* สไตล์สำหรับกล่องอัปโหลดที่ทำเนียนไปกับ Emoji */
    .custom-upload-box {
        width: 50px;
        height: 50px;
        border: 2px dashed #cbd5e1;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        color: #64748b;
        background: #f8fafc;
        transition: all 0.2s;
        position: relative;
    }

    .custom-upload-box:hover {
        border-color: #667eea;
        color: #667eea;
        background: #fff;
    }

    .custom-upload-box input[type="file"] {
        position: absolute;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
    }

    .custom-logo-preview {
        width: 100%;
        height: 100%;
        object-fit: contain;
        padding: 5px;
    }

    .preview-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 15px;
        border-radius: 8px;
        margin-top: 15px;
        display: flex;
        align-items: center;
        gap: 15px;
    }
</style>

<h2>🏢 จัดการข้อมูลหน่วยงาน</h2>

<?php echo $message; ?>

<div class="admin-form">
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="update_organization">

        <div class="form-section" style="border-bottom: 1px solid #eee; padding-bottom: 20px; margin-bottom: 20px;">
            <h3>📌 ชื่อและสัญลักษณ์</h3>

            <div class="form-group">
                <label>📛 ชื่อหน่วยงาน *</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($orgInfo['name'] ?? ''); ?>"
                    placeholder="เช่น สถาบันอุตสาหกรรมสุขภาพ" required>
            </div>

            <div class="form-group">
                <label>🎨 เลือกสัญลักษณ์ (Emoji หรือ อัปโหลดไฟล์ .ico/.png)</label>

                <div class="icon-section-wrapper">
                    <div class="custom-upload-box <?php echo $isCustomLogo ? 'active' : ''; ?>"
                        title="อัปโหลดรูปภาพใหม่">
                        <?php if ($isCustomLogo): ?>
                            <img src="../<?php echo htmlspecialchars($currentLogo); ?>" class="custom-logo-preview">
                        <?php else: ?>
                            <i class="fas fa-plus"></i>
                        <?php endif; ?>
                        <input type="file" name="custom_logo" accept=".jpg,.jpeg,.png,.gif,.webp,.ico"
                            onchange="previewUpload(this)">
                    </div>

                    <div class="icon-selector">
                        <?php
                        $emojis = ['🏥', '🏢', '🏛️', '🎓', '⚕️', '🔬', '🏆', '⭐', '🌟', '💼', '🎯', '🚀'];
                        foreach ($emojis as $emo) {
                            // ถ้าปัจจุบันไม่ใช่รูปภาพ และตรงกับ Emoji นี้ ให้ Active
                            $active = (!$isCustomLogo && $currentLogo === $emo) ? 'active' : '';
                            echo "<div class='icon-option $active' onclick=\"selectIcon('$emo')\">$emo</div>";
                        }
                        ?>
                    </div>
                </div>

                <p style="font-size: 12px; color: #666; margin-top: 5px;">* กดที่กล่อง <i class="fas fa-plus"
                        style="border:1px dashed #ccc; padding:2px;"></i> เพื่ออัปโหลดไฟล์ (.ico, .png, .jpg)</p>

                <input type="hidden" id="iconInput" name="icon" value="<?php echo htmlspecialchars($currentLogo); ?>">
            </div>

            <div class="preview-section">
                <div style="font-weight: bold; opacity: 0.8; font-size: 12px; margin-right: 10px;">ตัวอย่าง:</div>
                <div style="display: flex; align-items: center; gap: 10px; font-size: 20px; font-weight: bold;">
                    <div id="previewIcon">
                        <?php
                        if ($isCustomLogo) {
                            echo '<img src="../' . $currentLogo . '" style="height: 40px; border-radius: 4px; vertical-align: middle;">';
                        } else {
                            echo $currentLogo;
                        }
                        ?>
                    </div>
                    <div id="previewName"><?php echo htmlspecialchars($orgInfo['name'] ?? 'ชื่อหน่วยงาน'); ?></div>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3>📝 ข้อมูลทั่วไป</h3>
            <div class="form-group">
                <label>📄 คำอธิบายหน่วยงาน</label>
                <textarea name="description"
                    rows="3"><?php echo htmlspecialchars($orgInfo['description'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <label>🎯 วิสัยทัศน์ (Vision)</label>
                <textarea name="vision" rows="3"><?php echo htmlspecialchars($orgInfo['vision'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <label>🎪 พันธกิจ (Mission)</label>
                <textarea name="mission" rows="3"><?php echo htmlspecialchars($orgInfo['mission'] ?? ''); ?></textarea>
            </div>
        </div>

        <div class="form-section" style="margin-top: 20px;">
            <h3>📞 ข้อมูลติดต่อ</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label>📱 เบอร์โทรศัพท์</label>
                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($orgInfo['phone'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>✉️ อีเมล</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($orgInfo['email'] ?? ''); ?>">
                </div>
            </div>
            <div class="form-group">
                <label>📍 ที่อยู่</label>
                <textarea name="address" rows="3"><?php echo htmlspecialchars($orgInfo['address'] ?? ''); ?></textarea>
            </div>
        </div>

        <div style="margin-top: 20px;">
            <button type="submit" class="btn btn-primary" style="padding: 12px 25px; font-size: 16px;">💾
                บันทึกข้อมูล</button>
        </div>
    </form>
</div>

<script>
    function selectIcon(icon) {
        // ยกเลิก active ทั้งหมด (รวมถึงกล่องอัปโหลด)
        document.querySelectorAll('.icon-option, .custom-upload-box').forEach(el => el.classList.remove('active'));

        // เพิ่ม active ให้ Emoji ที่เลือก
        event.target.classList.add('active');

        // อัปเดตค่า input เป็น Emoji
        document.getElementById('iconInput').value = icon;

        // อัปเดต Preview เป็น Text Emoji
        document.getElementById('previewIcon').innerHTML = icon;

        // เคลียร์ค่าไฟล์ที่เลือกไว้ (เพื่อให้รู้ว่าเราเลือก Emoji แทน)
        document.querySelector('input[name="custom_logo"]').value = '';
    }

    function previewUpload(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();

            reader.onload = function (e) {
                // ยกเลิก active ที่ Emoji
                document.querySelectorAll('.icon-option').forEach(el => el.classList.remove('active'));

                // เพิ่ม active ให้กล่องอัปโหลด
                document.querySelector('.custom-upload-box').classList.add('active');

                // อัปเดต Preview เป็นรูปภาพ
                var imgHtml = '<img src="' + e.target.result + '" style="height: 40px; border-radius: 4px; vertical-align: middle;">';
                document.getElementById('previewIcon').innerHTML = imgHtml;

                // แสดงรูปในกล่องอัปโหลดด้วย (แทนไอคอนบวก)
                var box = document.querySelector('.custom-upload-box');
                // เก็บ input ไว้ นอกนั้นลบ (icon plus) แล้วใส่ img
                var fileInput = box.querySelector('input[type="file"]');
                box.innerHTML = '';
                var thumb = document.createElement('img');
                thumb.src = e.target.result;
                thumb.className = 'custom-logo-preview';
                box.appendChild(thumb);
                box.appendChild(fileInput); // ใส่ input กลับเข้าไป
            }

            reader.readAsDataURL(input.files[0]);
        }
    }

    // อัปเดตชื่อใน Preview Real-time
    document.querySelector('input[name="name"]').addEventListener('input', function () {
        document.getElementById('previewName').textContent = this.value || 'ชื่อหน่วยงาน';
    });
</script>