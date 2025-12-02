<?php
// ตรวจสอบ Session
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

$message = '';

// จัดการข้อมูลหน่วยงาน
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
        $icon = $_POST['icon'] ?? '🏥';

        if (empty($name)) {
            $message = '<div class="alert alert-error">✗ กรุณากรอกชื่อหน่วยงาน</div>';
        } else {
            try {
                $stmt = $pdo->prepare(
                    "UPDATE organization_info SET name=?, description=?, vision=?, mission=?, phone=?, email=?, address=?, logo=?, updated_at=NOW() WHERE id=1"
                );
                $stmt->execute([$name, $description, $vision, $mission, $phone, $email, $address, $icon]);
                $message = '<div class="alert alert-success">✓ อัพเดทข้อมูลหน่วยงานเสร็จแล้ว</div>';
            } catch (Exception $e) {
                $message = '<div class="alert alert-error">✗ เกิดข้อผิดพลาด</div>';
            }
        }
    }
}

// ดึงข้อมูลหน่วยงาน
$orgInfo = $pdo->query("SELECT * FROM organization_info LIMIT 1")->fetch();

// ถ้าไม่มีข้อมูลให้สร้างใหม่
if (!$orgInfo) {
    $pdo->query("INSERT INTO organization_info (name, description, vision, mission, logo) VALUES ('สถาบันอุตสาหกรรมสุขภาพ', '', '', '', '🏥')");
    $orgInfo = $pdo->query("SELECT * FROM organization_info LIMIT 1")->fetch();
}
?>

<style>
    .admin-form {
        background: white;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 30px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .form-section {
        margin-bottom: 30px;
        padding-bottom: 30px;
        border-bottom: 2px solid #ecf0f1;
    }

    .form-section:last-child {
        border-bottom: none;
    }

    .form-section h3 {
        margin-bottom: 20px;
        color: #2c3e50;
        font-size: 16px;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: bold;
        color: #333;
    }

    .form-group input,
    .form-group textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 14px;
        font-family: inherit;
    }

    .form-group textarea {
        resize: vertical;
        min-height: 100px;
    }

    .form-group input:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 5px rgba(102, 126, 234, 0.5);
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }

    .icon-selector {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 10px;
        margin-top: 10px;
    }

    .icon-option {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        aspect-ratio: 1;
        border: 2px solid #ddd;
        border-radius: 8px;
        font-size: 32px;
        cursor: pointer;
        transition: all 0.3s;
        background: #f8f9fa;
    }

    .icon-option:hover {
        border-color: #667eea;
        background: #eef2f8;
        transform: scale(1.1);
    }

    .icon-option.active {
        border-color: #667eea;
        background: #667eea;
        color: white;
        box-shadow: 0 0 10px rgba(102, 126, 234, 0.5);
    }

    .preview-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px;
        border-radius: 8px;
        margin-top: 20px;
    }

    .preview-section h4 {
        margin-bottom: 10px;
        font-size: 14px;
        opacity: 0.9;
    }

    .preview-navbar {
        display: flex;
        align-items: center;
        gap: 15px;
        font-size: 18px;
    }

    .preview-icon {
        font-size: 32px;
    }

    .preview-title {
        font-size: 20px;
        font-weight: bold;
    }

    .btn-group {
        display: flex;
        gap: 10px;
        margin-top: 20px;
    }

    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-weight: bold;
        transition: background 0.3s;
    }

    .btn-primary {
        background: #667eea;
        color: white;
    }

    .btn-primary:hover {
        background: #5568d3;
    }

    .btn-secondary {
        background: #6c757d;
        color: white;
    }

    .btn-secondary:hover {
        background: #5a6268;
    }

    .alert {
        padding: 12px;
        border-radius: 5px;
        margin-bottom: 20px;
    }

    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .alert-error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .info-box {
        background: #e7f5ff;
        color: #1971c2;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        border-left: 4px solid #1971c2;
    }

    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
        }

        .icon-selector {
            grid-template-columns: repeat(4, 1fr);
        }
    }
</style>

<h2>🏢 จัดการข้อมูลหน่วยงาน</h2>

<?php echo $message; ?>

<div class="info-box">
    <i class="fas fa-info-circle"></i>
    ข้อมูลที่แก้ไขที่นี่จะปรากฏในทุกส่วนของเว็บไซต์ (Navbar, Footer, Title)
</div>

<div class="admin-form">
    <form method="POST">
        <input type="hidden" name="action" value="update_organization">

        <!-- ส่วนชื่อหน่วยงานและ Icon -->
        <div class="form-section">
            <h3>📌 ชื่อหน่วยงานและไอคอน</h3>

            <div class="form-group">
                <label>📛 ชื่อหน่วยงาน *</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($orgInfo['name'] ?? ''); ?>"
                    placeholder="เช่น สถาบันอุตสาหกรรมสุขภาพ" required>
            </div>

            <div class="form-group">
                <label>🎨 เลือกไอคอน</label>
                <div class="icon-selector">
                    <div class="icon-option <?php echo ($orgInfo['logo'] === '🏥' ? 'active' : ''); ?>"
                        onclick="selectIcon('🏥')" title="โรงพยาบาล">🏥</div>
                    <div class="icon-option <?php echo ($orgInfo['logo'] === '🏢' ? 'active' : ''); ?>"
                        onclick="selectIcon('🏢')" title="สถาบัน">🏢</div>
                    <div class="icon-option <?php echo ($orgInfo['logo'] === '🏛️' ? 'active' : ''); ?>"
                        onclick="selectIcon('🏛️')" title="สำนักงาน">🏛️</div>
                    <div class="icon-option <?php echo ($orgInfo['logo'] === '🎓' ? 'active' : ''); ?>"
                        onclick="selectIcon('🎓')" title="การศึกษา">🎓</div>
                    <div class="icon-option <?php echo ($orgInfo['logo'] === '⚕️' ? 'active' : ''); ?>"
                        onclick="selectIcon('⚕️')" title="สุขภาพ">⚕️</div>
                    <div class="icon-option <?php echo ($orgInfo['logo'] === '🔬' ? 'active' : ''); ?>"
                        onclick="selectIcon('🔬')" title="วิทยาศาสตร์">🔬</div>
                    <div class="icon-option <?php echo ($orgInfo['logo'] === '🏆' ? 'active' : ''); ?>"
                        onclick="selectIcon('🏆')" title="รางวัล">🏆</div>
                    <div class="icon-option <?php echo ($orgInfo['logo'] === '⭐' ? 'active' : ''); ?>"
                        onclick="selectIcon('⭐')" title="ดาว">⭐</div>
                    <div class="icon-option <?php echo ($orgInfo['logo'] === '🌟' ? 'active' : ''); ?>"
                        onclick="selectIcon('🌟')" title="ดาวสว่าง">🌟</div>
                    <div class="icon-option <?php echo ($orgInfo['logo'] === '💼' ? 'active' : ''); ?>"
                        onclick="selectIcon('💼')" title="ธุรกิจ">💼</div>
                    <div class="icon-option <?php echo ($orgInfo['logo'] === '🎯' ? 'active' : ''); ?>"
                        onclick="selectIcon('🎯')" title="เป้าหมาย">🎯</div>
                    <div class="icon-option <?php echo ($orgInfo['logo'] === '🚀' ? 'active' : ''); ?>"
                        onclick="selectIcon('🚀')" title="ก้าวหน้า">🚀</div>
                </div>
                <input type="hidden" id="iconInput" name="icon"
                    value="<?php echo htmlspecialchars($orgInfo['logo'] ?? '🏥'); ?>">
            </div>

            <!-- Preview -->
            <div class="preview-section">
                <h4>👀 ตัวอย่างการแสดงผล (Navbar)</h4>
                <div class="preview-navbar">
                    <div class="preview-icon" id="previewIcon"><?php echo $orgInfo['logo'] ?? '🏥'; ?></div>
                    <div class="preview-title" id="previewName"><?php echo htmlspecialchars($orgInfo['name'] ?? ''); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ส่วนข้อมูลทั่วไป -->
        <div class="form-section">
            <h3>📝 ข้อมูลทั่วไป</h3>

            <div class="form-group">
                <label>📄 คำอธิบายหน่วยงาน</label>
                <textarea name="description"
                    placeholder="กรอกคำอธิบายเกี่ยวกับหน่วยงาน"><?php echo htmlspecialchars($orgInfo['description'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label>🎯 วิสัยทัศน์ (Vision)</label>
                <textarea name="vision"
                    placeholder="กรอกวิสัยทัศน์ของหน่วยงาน"><?php echo htmlspecialchars($orgInfo['vision'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label>🎪 พันธกิจ (Mission)</label>
                <textarea name="mission"
                    placeholder="กรอกพันธกิจของหน่วยงาน"><?php echo htmlspecialchars($orgInfo['mission'] ?? ''); ?></textarea>
            </div>
        </div>

        <!-- ส่วนข้อมูลติดต่อ -->
        <div class="form-section">
            <h3>📞 ข้อมูลติดต่อ</h3>

            <div class="form-row">
                <div class="form-group">
                    <label>📱 เบอร์โทรศัพท์</label>
                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($orgInfo['phone'] ?? ''); ?>"
                        placeholder="เช่น +66-XX-XXXX-XXXX">
                </div>

                <div class="form-group">
                    <label>✉️ อีเมล</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($orgInfo['email'] ?? ''); ?>"
                        placeholder="เช่น admin@hospital.com">
                </div>
            </div>

            <div class="form-group">
                <label>📍 ที่อยู่</label>
                <textarea name="address"
                    placeholder="กรอกที่อยู่ของหน่วยงาน"><?php echo htmlspecialchars($orgInfo['address'] ?? ''); ?></textarea>
            </div>
        </div>

        <!-- ปุ่มบันทึก -->
        <div class="btn-group">
            <button type="submit" class="btn btn-primary">💾 บันทึกข้อมูล</button>
        </div>
    </form>
</div>

<script>
    function selectIcon(icon) {
        // ยกเลิก active ทั้งหมด
        document.querySelectorAll('.icon-option').forEach(el => {
            el.classList.remove('active');
        });

        // เพิ่ม active ให้ไอคอนที่เลือก
        event.target.classList.add('active');

        // บันทึกค่า
        document.getElementById('iconInput').value = icon;

        // อัพเดท Preview
        document.getElementById('previewIcon').textContent = icon;
    }

    // อัพเดท Preview เมื่อพิมพ์ชื่อ
    document.querySelector('input[name="name"]').addEventListener('input', function () {
        document.getElementById('previewName').textContent = this.value || 'ชื่อหน่วยงาน';
    });
</script>