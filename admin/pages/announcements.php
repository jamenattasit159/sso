<?php
// admin/pages/announcements.php - จัดการประกาศข่าวสาร (Full Version with Categories)

// ตรวจสอบ Session
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

$message = '';

// จัดการประกาศ (Add / Update / Delete)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. เพิ่มประกาศใหม่
    if ($action == 'add_announce') {
        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? '';
        $status = $_POST['status'] ?? 'active';
        $category = $_POST['category'] ?? 'general'; // รับค่าหมวดหมู่
        $attachment = '';

        if (empty($title) || empty($content)) {
            $message = '<div class="alert alert-error">✗ กรุณากรอกชื่อและเนื้อหา</div>';
        } else {
            try {
                // จัดการไฟล์แนบ
                if (isset($_FILES['attachment']) && $_FILES['attachment']['size'] > 0) {
                    $file = $_FILES['attachment'];
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $allowedExt = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'jpg', 'jpeg', 'png', 'gif'];

                    if (in_array($ext, $allowedExt)) {
                        $filename = time() . '_' . md5(uniqid()) . '.' . $ext;
                        $uploadPath = '../uploads/files/';

                        if (!is_dir($uploadPath)) {
                            mkdir($uploadPath, 0755, true);
                        }

                        if (move_uploaded_file($file['tmp_name'], $uploadPath . $filename)) {
                            $attachment = $filename;
                        }
                    } else {
                        $message = '<div class="alert alert-error">✗ ประเภทไฟล์ไม่อนุญาต</div>';
                    }
                }

                if (empty($message)) {
                    // เพิ่ม category ใน SQL
                    $stmt = $pdo->prepare(
                        "INSERT INTO announcements (title, content, image, category, status, created_at) 
                         VALUES (?, ?, ?, ?, ?, NOW())"
                    );
                    $stmt->execute([$title, $content, $attachment, $category, $status]);
                    $message = '<div class="alert alert-success">✓ เพิ่มประกาศเสร็จแล้ว</div>';
                }
            } catch (Exception $e) {
                $message = '<div class="alert alert-error">✗ เกิดข้อผิดพลาด: ' . $e->getMessage() . '</div>';
            }
        }
    }

    // 2. แก้ไขประกาศ
    elseif ($action == 'update_announce') {
        $id = $_POST['id'] ?? '';
        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? '';
        $status = $_POST['status'] ?? 'active';
        $category = $_POST['category'] ?? 'general'; // รับค่าหมวดหมู่

        if (empty($id) || empty($title) || empty($content)) {
            $message = '<div class="alert alert-error">✗ ข้อมูลไม่ครบ</div>';
        } else {
            try {
                // ตรวจสอบไฟล์ใหม่
                if (isset($_FILES['attachment']) && $_FILES['attachment']['size'] > 0) {
                    $file = $_FILES['attachment'];
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $allowedExt = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'jpg', 'jpeg', 'png', 'gif'];

                    if (in_array($ext, $allowedExt)) {
                        $filename = time() . '_' . md5(uniqid()) . '.' . $ext;
                        $uploadPath = '../uploads/files/';

                        if (move_uploaded_file($file['tmp_name'], $uploadPath . $filename)) {
                            // ลบไฟล์เก่า
                            $oldAnnounce = $pdo->query("SELECT image FROM announcements WHERE id=" . intval($id))->fetch();
                            if ($oldAnnounce['image'] && file_exists('../uploads/files/' . $oldAnnounce['image'])) {
                                @unlink('../uploads/files/' . $oldAnnounce['image']);
                            }

                            // Update แบบมีรูปและหมวดหมู่
                            $stmt = $pdo->prepare(
                                "UPDATE announcements SET title=?, content=?, image=?, category=?, status=?, updated_at=NOW() WHERE id=?"
                            );
                            $stmt->execute([$title, $content, $filename, $category, $status, $id]);
                        }
                    } else {
                        $message = '<div class="alert alert-error">✗ ประเภทไฟล์ไม่อนุญาต</div>';
                    }
                } else {
                    // Update แบบไม่มีรูป (อัปเดตหมวดหมู่ด้วย)
                    $stmt = $pdo->prepare(
                        "UPDATE announcements SET title=?, content=?, category=?, status=?, updated_at=NOW() WHERE id=?"
                    );
                    $stmt->execute([$title, $content, $category, $status, $id]);
                }

                if (empty($message)) {
                    $message = '<div class="alert alert-success">✓ อัพเดทประกาศเสร็จแล้ว</div>';
                }
                unset($_GET['edit']);
            } catch (Exception $e) {
                $message = '<div class="alert alert-error">✗ เกิดข้อผิดพลาด</div>';
            }
        }
    }

    // 3. ลบประกาศ
    elseif ($action == 'delete_announce') {
        $id = $_POST['id'] ?? '';

        try {
            $announce = $pdo->query("SELECT image FROM announcements WHERE id=" . intval($id))->fetch();

            if ($announce && $announce['image'] && file_exists('../uploads/files/' . $announce['image'])) {
                @unlink('../uploads/files/' . $announce['image']);
            }

            $stmt = $pdo->prepare("DELETE FROM announcements WHERE id=?");
            $stmt->execute([$id]);
            $message = '<div class="alert alert-success">✓ ลบประกาศเสร็จแล้ว</div>';
        } catch (Exception $e) {
            $message = '<div class="alert alert-error">✗ ไม่สามารถลบได้</div>';
        }
    }
}

// ดึงรายการประกาศทั้งหมด
$announcements = $pdo->query("SELECT * FROM announcements ORDER BY created_at DESC")->fetchAll();

// ตรวจสอบโหมดแก้ไข
$editId = $_GET['edit'] ?? '';
$editAnnounce = null;
if ($editId) {
    $editAnnounce = $pdo->query("SELECT * FROM announcements WHERE id=" . intval($editId))->fetch();
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
    .form-group textarea,
    .form-group select {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 14px;
    }

    .form-group textarea {
        resize: vertical;
        min-height: 150px;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }

    .upload-area {
        border: 2px dashed #667eea;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
        background: #f8f9fa;
        margin-bottom: 10px;
        cursor: pointer;
    }

    .upload-area:hover {
        background: #eef2f8;
        border-color: #5568d3;
    }

    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-weight: bold;
    }

    .btn-primary {
        background: #667eea;
        color: white;
    }

    .btn-secondary {
        background: #6c757d;
        color: white;
    }

    .btn-danger {
        background: #dc3545;
        color: white;
    }

    .btn-sm {
        padding: 6px 12px;
        font-size: 12px;
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

    .table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .table th,
    .table td {
        padding: 15px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }

    .table thead {
        background: #f8f9fa;
    }

    .category-badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: bold;
        display: inline-block;
    }

    .badge-download {
        background: #dbeafe;
        color: #1e40af;
    }

    .badge-procurement {
        background: #ffedd5;
        color: #9a3412;
    }

    .badge-ita {
        background: #f3e8ff;
        color: #6b21a8;
    }

    .badge-general {
        background: #f3f4f6;
        color: #374151;
    }
</style>

<h2>📢 จัดการประกาศและไฟล์แนบ</h2>

<?php echo $message; ?>

<div class="admin-form">
    <h3><?php echo $editAnnounce ? 'แก้ไขประกาศ' : 'เพิ่มประกาศใหม่'; ?></h3>

    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="<?php echo $editAnnounce ? 'update_announce' : 'add_announce'; ?>">
        <?php if ($editAnnounce): ?>
            <input type="hidden" name="id" value="<?php echo $editAnnounce['id']; ?>">
        <?php endif; ?>

        <div class="form-group">
            <label>📌 หัวข้อประกาศ *</label>
            <input type="text" name="title" value="<?php echo htmlspecialchars($editAnnounce['title'] ?? ''); ?>"
                required placeholder="เช่น ประกาศรับสมัครงาน...">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>📂 หมวดหมู่ *</label>
                <select name="category" required>
                    <?php $cat = $editAnnounce['category'] ?? 'general'; ?>
                    <option value="general" <?php echo $cat == 'general' ? 'selected' : ''; ?>>📢 ประกาศทั่วไป</option>
                    <option value="download" <?php echo $cat == 'download' ? 'selected' : ''; ?>>⬇️ ดาวน์โหลด</option>
                    <option value="procurement" <?php echo $cat == 'procurement' ? 'selected' : ''; ?>>🏗️ จัดซื้อจัดจ้าง
                    </option>
                    <option value="ita" <?php echo $cat == 'ita' ? 'selected' : ''; ?>>⚖️ ประกาศ ITA</option>
                </select>
            </div>

            <div class="form-group">
                <label>🔗 สถานะ</label>
                <select name="status">
                    <option value="active" <?php echo (($editAnnounce['status'] ?? 'active') === 'active' ? 'selected' : ''); ?>>✓ เปิดใช้งาน</option>
                    <option value="inactive" <?php echo (($editAnnounce['status'] ?? '') === 'inactive' ? 'selected' : ''); ?>>✗ ปิดใช้งาน</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>📝 เนื้อหา *</label>
            <textarea name="content" placeholder="รายละเอียด..."
                required><?php echo htmlspecialchars($editAnnounce['content'] ?? ''); ?></textarea>
        </div>

        <div class="form-group">
            <label>📎 ไฟล์แนบ (Optional)</label>
            <div class="upload-area" onclick="document.getElementById('attachmentInput').click()">
                <div style="font-size: 24px; margin-bottom: 8px;">📁</div>
                <p style="margin: 0; font-size: 14px;">คลิกเพื่อเลือกไฟล์ (PDF, Word, Excel, รูปภาพ)</p>
            </div>
            <input type="file" id="attachmentInput" name="attachment" style="display: none;"
                accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.jpg,.jpeg,.png,.gif">
            <div id="fileSelected" style="margin-top: 5px; font-size: 13px; color: #666; display: none;"></div>

            <?php if ($editAnnounce && $editAnnounce['image']): ?>
                <div
                    style="margin-top: 10px; padding: 5px; background: #eef2f8; border-radius: 4px; font-size: 12px; display: inline-block;">
                    📎 ไฟล์ปัจจุบัน: <a href="../uploads/files/<?php echo htmlspecialchars($editAnnounce['image']); ?>"
                        target="_blank"><?php echo htmlspecialchars($editAnnounce['image']); ?></a>
                </div>
            <?php endif; ?>
        </div>

        <div class="btn-group">
            <button type="submit"
                class="btn btn-primary"><?php echo $editAnnounce ? '✓ บันทึกแก้ไข' : '✓ เพิ่มประกาศ'; ?></button>
            <?php if ($editAnnounce): ?>
                <a href="?page=announcements" class="btn btn-secondary" style="text-decoration: none;">ยกเลิก</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<h3>📋 รายการประกาศทั้งหมด (<?php echo count($announcements); ?>)</h3>
<div style="overflow-x: auto;">
    <table class="table">
        <thead>
            <tr>
                <th>หัวข้อประกาศ</th>
                <th>หมวดหมู่</th>
                <th>สถานะ</th>
                <th>ไฟล์แนบ</th>
                <th>วันที่</th>
                <th>จัดการ</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($announcements)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; color: #999; padding: 30px;">ยังไม่มีประกาศ</td>
                </tr>
            <?php else: ?>
                <?php foreach ($announcements as $announce): ?>
                    <tr>
                        <td style="max-width: 300px;">
                            <strong><?php echo htmlspecialchars(substr($announce['title'], 0, 50)); ?></strong>
                        </td>
                        <td>
                            <?php
                            $c = $announce['category'] ?? 'general';
                            $badges = [
                                'general' => ['bg' => 'badge-general', 'label' => '📢 ทั่วไป'],
                                'download' => ['bg' => 'badge-download', 'label' => '⬇️ ดาวน์โหลด'],
                                'procurement' => ['bg' => 'badge-procurement', 'label' => '🏗️ จัดซื้อ'],
                                'ita' => ['bg' => 'badge-ita', 'label' => '⚖️ ITA']
                            ];
                            $b = $badges[$c] ?? $badges['general'];
                            ?>
                            <span class="category-badge <?php echo $b['bg']; ?>"><?php echo $b['label']; ?></span>
                        </td>
                        <td>
                            <?php echo $announce['status'] === 'active'
                                ? '<span style="color:green; font-size:12px;">✓ เปิด</span>'
                                : '<span style="color:red; font-size:12px;">✗ ปิด</span>'; ?>
                        </td>
                        <td>
                            <?php if ($announce['image']): ?>
                                <a href="../uploads/files/<?php echo htmlspecialchars($announce['image']); ?>" target="_blank"
                                    style="font-size: 18px; text-decoration: none;">📎</a>
                            <?php else: ?>
                                <span style="color:#ccc;">-</span>
                            <?php endif; ?>
                        </td>
                        <td><small><?php echo date('d/m/y', strtotime($announce['created_at'])); ?></small></td>
                        <td>
                            <a href="?page=announcements&edit=<?php echo $announce['id']; ?>"
                                class="btn btn-primary btn-sm">✎</a>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('ยืนยันการลบ?');">
                                <input type="hidden" name="action" value="delete_announce">
                                <input type="hidden" name="id" value="<?php echo $announce['id']; ?>">
                                <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
    document.getElementById('attachmentInput').addEventListener('change', function (e) {
        if (this.files && this.files[0]) {
            document.getElementById('fileSelected').textContent = '✓ เลือกไฟล์: ' + this.files[0].name;
            document.getElementById('fileSelected').style.display = 'block';
        }
    });
</script>