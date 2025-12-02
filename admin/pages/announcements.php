<?php
// ตรวจสอบ Session
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

$message = '';

// จัดการประกาศ
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action == 'add_announce') {
        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? '';
        $status = $_POST['status'] ?? 'active';
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
                        $message = '<div class="alert alert-error">✗ ประเภทไฟล์ไม่อนุญาต (PDF, Word, Excel, PowerPoint, ZIP, รูปภาพ)</div>';
                    }
                }

                if (empty($message)) {
                    $stmt = $pdo->prepare(
                        "INSERT INTO announcements (title, content, image, status, created_at) 
                         VALUES (?, ?, ?, ?, NOW())"
                    );
                    $stmt->execute([$title, $content, $attachment, $status]);
                    $message = '<div class="alert alert-success">✓ เพิ่มประกาศเสร็จแล้ว</div>';
                }
            } catch (Exception $e) {
                $message = '<div class="alert alert-error">✗ เกิดข้อผิดพลาด: ' . $e->getMessage() . '</div>';
            }
        }
    } elseif ($action == 'update_announce') {
        $id = $_POST['id'] ?? '';
        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? '';
        $status = $_POST['status'] ?? 'active';

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

                            $stmt = $pdo->prepare(
                                "UPDATE announcements SET title=?, content=?, image=?, status=?, updated_at=NOW() WHERE id=?"
                            );
                            $stmt->execute([$title, $content, $filename, $status, $id]);
                        }
                    } else {
                        $message = '<div class="alert alert-error">✗ ประเภทไฟล์ไม่อนุญาต</div>';
                    }
                } else {
                    $stmt = $pdo->prepare(
                        "UPDATE announcements SET title=?, content=?, status=?, updated_at=NOW() WHERE id=?"
                    );
                    $stmt->execute([$title, $content, $status, $id]);
                }

                if (empty($message)) {
                    $message = '<div class="alert alert-success">✓ อัพเดทประกาศเสร็จแล้ว</div>';
                }
                unset($_GET['edit']);
            } catch (Exception $e) {
                $message = '<div class="alert alert-error">✗ เกิดข้อผิดพลาด</div>';
            }
        }
    } elseif ($action == 'delete_announce') {
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

$announcements = $pdo->query("SELECT * FROM announcements ORDER BY created_at DESC")->fetchAll();

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
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
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
        font-family: inherit;
    }
    
    .form-group textarea {
        resize: vertical;
        min-height: 150px;
    }
    
    .form-group input:focus,
    .form-group textarea:focus,
    .form-group select:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 5px rgba(102, 126, 234, 0.5);
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
        transition: all 0.3s;
        cursor: pointer;
    }
    
    .upload-area:hover {
        background: #eef2f8;
        border-color: #5568d3;
    }
    
    .upload-area input[type="file"] {
        display: none;
    }
    
    .file-info {
        font-size: 12px;
        color: #666;
        margin-top: 5px;
    }
    
    .btn-group {
        display: flex;
        gap: 10px;
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
    
    .btn-danger {
        background: #dc3545;
        color: white;
    }
    
    .btn-danger:hover {
        background: #c82333;
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
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .table thead {
        background: #f8f9fa;
    }
    
    .table th,
    .table td {
        padding: 15px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }
    
    .table tbody tr:hover {
        background: #f8f9fa;
    }
    
    .action-buttons {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
    }
    
    .file-badge {
        display: inline-block;
        background: #cfe2ff;
        color: #084298;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11px;
        margin-top: 5px;
    }
    
    .no-file {
        color: #999;
        font-size: 12px;
    }
    
    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
        }
        
        .action-buttons {
            flex-direction: column;
        }
        
        .action-buttons button,
        .action-buttons a {
            width: 100%;
        }
        
        .table {
            font-size: 12px;
        }
        
        .table th,
        .table td {
            padding: 10px;
        }
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
            <input type="text" name="title" 
                   value="<?php echo htmlspecialchars($editAnnounce['title'] ?? ''); ?>" 
                   placeholder="กรอกหัวข้อประกาศ" required>
        </div>
        
        <div class="form-group">
            <label>📝 เนื้อหา *</label>
            <textarea name="content" 
                      placeholder="กรอกเนื้อหาประกาศ" required><?php echo htmlspecialchars($editAnnounce['content'] ?? ''); ?></textarea>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>🔗 สถานะ *</label>
                <select name="status">
                    <option value="active" <?php echo (($editAnnounce['status'] ?? 'active') === 'active' ? 'selected' : ''); ?>>
                        ✓ เปิดใช้งาน
                    </option>
                    <option value="inactive" <?php echo (($editAnnounce['status'] ?? '') === 'inactive' ? 'selected' : ''); ?>>
                        ✗ ปิดใช้งาน
                    </option>
                </select>
            </div>
            
            <div class="form-group">
                <label>📎 ไฟล์แนบ (Optional)</label>
                <div class="upload-area" onclick="document.getElementById('attachmentInput').click()">
                    <div style="font-size: 24px; margin-bottom: 8px;">📁</div>
                    <p style="margin: 0; font-size: 14px; font-weight: 500;">คลิกเพื่อเลือกไฟล์</p>
                </div>
                <input type="file" id="attachmentInput" name="attachment" 
                       accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.jpg,.jpeg,.png,.gif">
                <div class="file-info">
                    ✓ ยอมรับ: PDF, Word, Excel, PowerPoint, ZIP, รูปภาพ (สูงสุด 50MB)
                </div>
                <?php if ($editAnnounce && $editAnnounce['image']): ?>
                        <div class="file-badge">
                            📎 ไฟล์ปัจจุบัน: <?php echo htmlspecialchars($editAnnounce['image']); ?>
                        </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div id="fileSelected" style="margin: 10px 0; display: none;">
            <div style="background: #d1ecf1; color: #0c5460; padding: 10px; border-radius: 5px; font-size: 13px;">
                ✓ เลือกไฟล์: <strong id="fileName"></strong>
            </div>
        </div>
        
        <div class="btn-group">
            <button type="submit" class="btn btn-primary">
                <?php echo $editAnnounce ? '✓ อัพเดท' : '✓ เพิ่ม'; ?>
            </button>
            <?php if ($editAnnounce): ?>
                    <a href="?page=announcements" class="btn btn-secondary" style="text-decoration: none;">✕ ยกเลิก</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<h3>📋 รายการประกาศ (<?php echo count($announcements); ?>)</h3>
<div style="overflow-x: auto;">
    <table class="table">
        <thead>
            <tr>
                <th>หัวข้อประกาศ</th>
                <th>สถานะ</th>
                <th>ไฟล์แนบ</th>
                <th>วันที่สร้าง</th>
                <th>การดำเนิน</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($announcements)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: #999; padding: 30px;">
                            <i style="font-size: 32px; display: block; margin-bottom: 10px;">📭</i>
                            ยังไม่มีประกาศ
                        </td>
                    </tr>
            <?php else: ?>
                    <?php foreach ($announcements as $announce): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars(substr($announce['title'], 0, 35)); ?></strong>
                                    <p style="font-size: 12px; color: #666; margin-top: 5px; margin-bottom: 0;">
                                        <?php echo htmlspecialchars(substr(strip_tags($announce['content']), 0, 50)); ?>...
                                    </p>
                                </td>
                                <td>
                                    <span style="<?php echo $announce['status'] === 'active' ? 'background: #d4edda; color: #155724;' : 'background: #f8d7da; color: #721c24;'; ?> padding: 4px 8px; border-radius: 4px; font-size: 12px; white-space: nowrap;">
                                        <?php echo $announce['status'] === 'active' ? '✓ เปิด' : '✗ ปิด'; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($announce['image']): ?>
                                            <a href="../uploads/files/<?php echo htmlspecialchars($announce['image']); ?>" 
                                               download 
                                               style="color: #667eea; text-decoration: none; font-size: 12px;">
                                                📎 <?php echo strtoupper(pathinfo($announce['image'], PATHINFO_EXTENSION)); ?>
                                            </a>
                                    <?php else: ?>
                                            <span class="no-file">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small><?php echo date('d/m/Y H:i', strtotime($announce['created_at'])); ?></small>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="?page=announcements&edit=<?php echo $announce['id']; ?>" 
                                           class="btn btn-primary btn-sm">✎ แก้ไข</a>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="delete_announce">
                                            <input type="hidden" name="id" value="<?php echo $announce['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" 
                                                    onclick="return confirm('ต้องการลบประกาศ &quot;<?php echo htmlspecialchars(substr($announce['title'], 0, 20)); ?>&quot; หรือไม่?')">
                                                🗑️ ลบ
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                    <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
    // แสดงชื่อไฟล์เมื่อเลือก
    document.getElementById('attachmentInput').addEventListener('change', function(e) {
        const fileSelected = document.getElementById('fileSelected');
        const fileName = document.getElementById('fileName');
        
        if (this.files && this.files[0]) {
            fileName.textContent = this.files[0].name;
            fileSelected.style.display = 'block';
        } else {
            fileSelected.style.display = 'none';
        }
    });
    
    // ลากไฟล์มาวาง
    const uploadArea = document.querySelector('.upload-area');
    const fileInput = document.getElementById('attachmentInput');
    
    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.style.borderColor = '#5568d3';
        uploadArea.style.background = '#eef2f8';
    });
    
    uploadArea.addEventListener('dragleave', () => {
        uploadArea.style.borderColor = '#667eea';
        uploadArea.style.background = '#f8f9fa';
    });
    
    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.style.borderColor = '#667eea';
        uploadArea.style.background = '#f8f9fa';
        fileInput.files = e.dataTransfer.files;
        
        // แสดงชื่อไฟล์
        if (fileInput.files && fileInput.files[0]) {
            document.getElementById('fileName').textContent = fileInput.files[0].name;
            document.getElementById('fileSelected').style.display = 'block';
        }
    });
</script>