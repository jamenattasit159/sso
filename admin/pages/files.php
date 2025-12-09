<?php
// admin/pages/files.php - จัดการรูปประชาสัมพันธ์ (แก้ไขใหม่)

// ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. ส่วนอัปโหลดไฟล์
    if ($action == 'upload' && isset($_FILES['file'])) {
        $file = $_FILES['file'];
        // กำหนดหมวดหมู่ตายตัวว่าเป็น 'pr_image'
        $category = 'pr_image';

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        // อนุญาตเฉพาะไฟล์รูปภาพ
        $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($ext, $allowedExt)) {
            $filename = time() . '_' . md5(uniqid()) . '.' . $ext;
            $uploadPath = '../uploads/files/';

            // สร้างโฟลเดอร์ถ้ายังไม่มี
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            if (move_uploaded_file($file['tmp_name'], $uploadPath . $filename)) {
                // บันทึกชื่อไฟล์เดิมเพื่อใช้เป็น caption (ถ้าต้องการ)
                $originalName = pathinfo($file['name'], PATHINFO_FILENAME);

                $stmt = $pdo->prepare(
                    "INSERT INTO files (filename, filepath, file_type, category, status, created_at) 
                     VALUES (?, ?, ?, ?, 'active', NOW())"
                );
                $stmt->execute([$originalName, $filename, $ext, $category]);
                $message = '<div class="alert alert-success">✓ อัปโหลดรูปภาพสำเร็จ</div>';
            } else {
                $message = '<div class="alert alert-danger">✗ ไม่สามารถย้ายไฟล์ได้</div>';
            }
        } else {
            $message = '<div class="alert alert-danger">✗ อนุญาตเฉพาะไฟล์รูปภาพ (JPG, PNG, GIF) เท่านั้น</div>';
        }
    }

    // 2. ส่วนลบไฟล์
    elseif ($action == 'delete') {
        $id = $_POST['id'] ?? '';
        $file = $pdo->query("SELECT filepath FROM files WHERE id=$id")->fetch();

        if ($file && file_exists('../uploads/files/' . $file['filepath'])) {
            unlink('../uploads/files/' . $file['filepath']);
        }

        $stmt = $pdo->prepare("DELETE FROM files WHERE id=?");
        $stmt->execute([$id]);
        $message = '<div class="alert alert-success">✓ ลบรูปภาพเรียบร้อย</div>';
    }
}

// ดึงข้อมูลไฟล์ (กรองเฉพาะที่เป็นรูปภาพ หรือหมวด pr_image)
$files = $pdo->query("SELECT * FROM files WHERE file_type IN ('jpg', 'jpeg', 'png', 'gif', 'webp') ORDER BY created_at DESC")->fetchAll();
?>

<style>
    /* สไตล์สำหรับกล่องอัปโหลด */
    .upload-area {
        border: 2px dashed #cbd5e1;
        border-radius: 12px;
        padding: 40px;
        text-align: center;
        background: #f8fafc;
        cursor: pointer;
        transition: all 0.3s;
        position: relative;
    }

    .upload-area:hover {
        background: #fff;
        border-color: #667eea;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.1);
    }

    /* สไตล์สำหรับ Grid แสดงรูปภาพ */
    .gallery-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }

    .gallery-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        overflow: hidden;
        transition: transform 0.2s, box-shadow 0.2s;
        position: relative;
    }

    .gallery-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }

    .img-container {
        height: 160px;
        width: 100%;
        background: #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    .img-container img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        /* ทำให้รูปเต็มกรอบสวยงาม */
        transition: transform 0.3s;
    }

    .gallery-card:hover .img-container img {
        transform: scale(1.05);
    }

    .card-actions {
        padding: 12px;
        border-top: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: white;
    }

    .file-name {
        font-size: 13px;
        color: #334155;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 120px;
        font-weight: 500;
    }

    /* ตัวอย่างรูปก่อนอัปโหลด (Preview) */
    #imagePreview {
        max-width: 100%;
        max-height: 250px;
        border-radius: 8px;
        margin-top: 15px;
        display: none;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .alert {
        padding: 12px;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    .alert-success {
        background: #dcfce7;
        color: #166534;
        border: 1px solid #bbf7d0;
    }

    .alert-danger {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }
</style>

<h2>🖼️ จัดการรูปประชาสัมพันธ์</h2>
<p style="color: #64748b; font-size: 14px; margin-bottom: 20px;">อัปโหลดรูปภาพกิจกรรม ข่าวสาร
    หรือภาพทั่วไปเพื่อนำไปแสดงผลบนหน้าเว็บไซต์</p>

<?php echo $message; ?>

<div class="admin-form">
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="upload">

        <div class="upload-area" onclick="document.getElementById('fileInput').click()">
            <div id="uploadPlaceholder">
                <i class="fas fa-cloud-upload-alt" style="font-size: 48px; color: #94a3b8; margin-bottom: 15px;"></i>
                <h3 style="color: #475569; margin: 0 0 5px 0;">คลิกเพื่อเลือกรูปภาพ</h3>
                <p style="color: #94a3b8; font-size: 13px; margin: 0;">รองรับ JPG, PNG, GIF (สูงสุด 10MB)</p>
            </div>

            <img id="imagePreview" src="#" alt="ตัวอย่างรูปภาพ">

            <input type="file" id="fileInput" name="file" accept="image/*" required style="display: none;"
                onchange="showPreview(this)">
        </div>

        <div style="margin-top: 15px; text-align: center;">
            <button type="submit" class="btn btn-primary" style="padding: 10px 30px;">
                <i class="fas fa-save"></i> บันทึกรูปภาพ
            </button>
        </div>
    </form>
</div>

<h3 style="margin-top: 30px; padding-bottom: 10px; border-bottom: 2px solid #e2e8f0;">
    📸 รูปภาพทั้งหมด (<?php echo count($files); ?>)
</h3>

<?php if (empty($files)): ?>
    <div
        style="text-align: center; padding: 50px; color: #94a3b8; background: white; border-radius: 12px; margin-top: 20px;">
        <i class="fas fa-images" style="font-size: 40px; margin-bottom: 15px;"></i>
        <p>ยังไม่มีรูปภาพในระบบ</p>
    </div>
<?php else: ?>
    <div class="gallery-grid">
        <?php foreach ($files as $file): ?>
            <div class="gallery-card">
                <div class="img-container">
                    <img src="../uploads/files/<?php echo htmlspecialchars($file['filepath']); ?>"
                        alt="<?php echo htmlspecialchars($file['filename']); ?>" loading="lazy">
                </div>

                <div class="card-actions">
                    <div title="<?php echo htmlspecialchars($file['filename']); ?>">
                        <div class="file-name"><?php echo htmlspecialchars($file['filename']); ?></div>
                        <small style="font-size: 10px; color: #94a3b8;">
                            <?php echo date('d/m/Y', strtotime($file['created_at'])); ?>
                        </small>
                    </div>

                    <form method="POST" onsubmit="return confirm('ยืนยันการลบรูปนี้?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo $file['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-danger" style="padding: 5px 10px;">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
    // ฟังก์ชันแสดงตัวอย่างรูปก่อนอัปโหลด
    function showPreview(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();

            reader.onload = function (e) {
                // ซ่อน Placeholder
                document.getElementById('uploadPlaceholder').style.display = 'none';

                // แสดงรูป Preview
                var preview = document.getElementById('imagePreview');
                preview.src = e.target.result;
                preview.style.display = 'inline-block';
            }

            reader.readAsDataURL(input.files[0]);
        }
    }

    // Drag & Drop
    const uploadArea = document.querySelector('.upload-area');
    const fileInput = document.getElementById('fileInput');

    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.style.borderColor = '#667eea';
        uploadArea.style.background = '#f1f5f9';
    });

    uploadArea.addEventListener('dragleave', () => {
        uploadArea.style.borderColor = '#cbd5e1';
        uploadArea.style.background = '#f8fafc';
    });

    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.style.borderColor = '#cbd5e1';
        uploadArea.style.background = '#f8fafc';

        if (e.dataTransfer.files.length > 0) {
            fileInput.files = e.dataTransfer.files;
            showPreview(fileInput);
        }
    });
</script>