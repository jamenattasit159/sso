<?php
// จัดการไฟล์
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'upload' && isset($_FILES['file'])) {
        $file = $_FILES['file'];
        $category = $_POST['category'] ?? 'other';
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = time() . '_' . md5($file['name']) . '.' . $ext;
        $filepath = '../uploads/files/' . $filename;
        
        // ตรวจสอบประเภทไฟล์
        $allowedExt = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array(strtolower($ext), $allowedExt)) {
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                $stmt = $pdo->prepare(
                    "INSERT INTO files (filename, filepath, file_type, category, status, created_at) 
                     VALUES (?, ?, ?, ?, 'active', NOW())"
                );
                $stmt->execute([$file['name'], $filename, $ext, $category]);
                $message = "✓ อัพโหลดไฟล์เสร็จแล้ว";
            } else {
                $message = "✗ ไม่สามารถอัพโหลดไฟล์ได้";
            }
        } else {
            $message = "✗ ประเภทไฟล์ไม่อนุญาต";
        }
    }
    
    elseif ($action == 'delete') {
        $id = $_POST['id'] ?? '';
        $file = $pdo->query("SELECT filepath FROM files WHERE id=$id")->fetch();
        
        if ($file && file_exists('../uploads/files/' . $file['filepath'])) {
            unlink('../uploads/files/' . $file['filepath']);
        }
        
        $stmt = $pdo->prepare("DELETE FROM files WHERE id=?");
        $stmt->execute([$id]);
        $message = "✓ ลบไฟล์เสร็จแล้ว";
    }
}

$files = $pdo->query("SELECT * FROM files ORDER BY created_at DESC")->fetchAll();
?>

<style>
    .file-section {
        background: white;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 30px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .upload-area {
        border: 2px dashed #667eea;
        border-radius: 8px;
        padding: 30px;
        text-align: center;
        background: #f8f9fa;
        margin-bottom: 20px;
        transition: all 0.3s;
    }
    
    .upload-area:hover {
        background: #eef2f8;
        border-color: #5568d3;
    }
    
    .upload-area input[type="file"] {
        display: none;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 15px;
        margin-bottom: 15px;
    }
    
    .files-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 15px;
        margin-top: 20px;
    }
    
    .file-card {
        background: white;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 15px;
        text-align: center;
        transition: all 0.3s;
    }
    
    .file-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    .file-icon {
        font-size: 40px;
        margin-bottom: 10px;
    }
    
    .file-name {
        font-weight: bold;
        color: #333;
        margin-bottom: 8px;
        word-break: break-word;
        font-size: 12px;
    }
    
    .file-meta {
        font-size: 12px;
        color: #666;
        margin-bottom: 10px;
    }
    
    .file-actions {
        display: flex;
        gap: 5px;
        justify-content: center;
    }
    
    .file-actions a,
    .file-actions button {
        font-size: 12px;
        padding: 5px 10px;
    }
    
    .message {
        padding: 12px;
        border-radius: 5px;
        margin-bottom: 20px;
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .btn-upload {
        background: #667eea;
        color: white;
        padding: 12px 24px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-weight: bold;
    }
    
    .btn-upload:hover {
        background: #5568d3;
    }
</style>

<h2>📄 จัดการไฟล์</h2>

<?php if (isset($message)): ?>
    <div class="message"><?php echo $message; ?></div>
<?php endif; ?>

<div class="file-section">
    <h3>📤 อัพโหลดไฟล์ใหม่</h3>
    
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="upload">
        
        <div class="upload-area" onclick="document.getElementById('fileInput').click()">
            <p style="font-size: 18px; margin-bottom: 10px;">📁 คลิกเพื่อเลือกไฟล์</p>
            <p style="color: #666; margin: 0;">หรือลากไฟล์มาวางที่นี่</p>
            <input type="file" id="fileInput" name="file" required>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>หมวดหมู่:</label>
                <select name="category" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                    <option value="announcement">ประกาศ</option>
                    <option value="document">เอกสาร</option>
                    <option value="report">รายงาน</option>
                    <option value="other">อื่นๆ</option>
                </select>
            </div>
            <div style="display: flex; align-items: flex-end;">
                <button type="submit" class="btn-upload">อัพโหลด</button>
            </div>
        </div>
    </form>
</div>

<div class="file-section">
    <h3>📋 รายการไฟล์</h3>
    
    <?php if (empty($files)): ?>
        <p style="color: #999;">ยังไม่มีไฟล์</p>
    <?php else: ?>
        <div class="files-grid">
            <?php foreach ($files as $file): ?>
                <div class="file-card">
                    <div class="file-icon">
                        <?php 
                        $ext = strtolower($file['file_type']);
                        $icons = [
                            'pdf' => '📄',
                            'doc' => '📝',
                            'docx' => '📝',
                            'xls' => '📊',
                            'xlsx' => '📊',
                            'ppt' => '📑',
                            'pptx' => '📑',
                            'zip' => '🗜️',
                            'jpg' => '🖼️',
                            'jpeg' => '🖼️',
                            'png' => '🖼️',
                            'gif' => '🖼️'
                        ];
                        echo $icons[$ext] ?? '📦';
                        ?>
                    </div>
                    <div class="file-name" title="<?php echo htmlspecialchars($file['filename']); ?>">
                        <?php echo htmlspecialchars(substr($file['filename'], 0, 20)); ?>
                    </div>
                    <div class="file-meta">
                        <?php echo strtoupper($file['file_type']); ?> | <?php echo htmlspecialchars($file['category']); ?>
                    </div>
                    <div class="file-meta">
                        <?php echo date('d/m/Y', strtotime($file['created_at'])); ?>
                    </div>
                    <div class="file-actions">
                        <a href="../uploads/files/<?php echo htmlspecialchars($file['filepath']); ?>" 
                           class="btn btn-sm" download>ดาวน์โหลด</a>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $file['id']; ?>">
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('ต้องการลบหรือไม่?')">ลบ</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
    // ลากไฟล์มาวาง
    const uploadArea = document.querySelector('.upload-area');
    const fileInput = document.getElementById('fileInput');
    
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
    });
</script>