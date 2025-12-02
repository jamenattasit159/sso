<?php
// admin/pages/manage_pages.php
if (!isset($_SESSION['admin_id']))
    header('Location: ../login.php');

$message = '';
$popupScript = ''; // ตัวแปรสำหรับเก็บ script popup

// จัดการ Form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. บันทึกหน้าเพจ
    if ($action == 'save') {
        $title = $_POST['title'];
        $content = $_POST['content'];
        $id = $_POST['id'] ?? '';

        $savedId = 0;

        if ($id) {
            $stmt = $pdo->prepare("UPDATE custom_pages SET title=?, content=? WHERE id=?");
            $stmt->execute([$title, $content, $id]);
            $savedId = $id;
            $message = '<div class="alert alert-success">✓ บันทึกแก้ไขเรียบร้อย</div>';
        } else {
            $stmt = $pdo->prepare("INSERT INTO custom_pages (title, content) VALUES (?, ?)");
            $stmt->execute([$title, $content]);
            $savedId = $pdo->lastInsertId();

            // *** จุดสำคัญ: สร้าง Script Popup ถาม user ***
            // ส่งค่า ID และ Title ไปให้ JavaScript
            $encodedId = encode_id($savedId); // เข้ารหัส ID ก่อนส่ง
            $safeTitle = htmlspecialchars($title, ENT_QUOTES);

            $popupScript = "
                <script>
                    setTimeout(function() {
                        if(confirm('✅ บันทึกหน้าเพจ \"$safeTitle\" เรียบร้อย!\\n\\nต้องการสร้าง \"ปุ่มเมนู\" ทางซ้ายเพื่อลิ้งค์มาหน้านี้เลยไหม?')) {
                            window.location.href = '?page=manage_pages&action=auto_create_btn&page_id=$savedId&title=$safeTitle';
                        }
                    }, 500);
                </script>
            ";

            $message = '<div class="alert alert-success">✓ สร้างหน้าใหม่เรียบร้อย</div>';
        }
    }
    // 2. ลบหน้าเพจ
    elseif ($action == 'delete') {
        $pdo->prepare("DELETE FROM custom_pages WHERE id=?")->execute([$_POST['id']]);
        $message = '<div class="alert alert-success">✓ ลบหน้าเพจเรียบร้อย</div>';
    }
}

// 3. (GET) ฟังก์ชันสร้างปุ่มอัตโนมัติ (ทำงานเมื่อ User กด OK ที่ Popup)
if (isset($_GET['action']) && $_GET['action'] == 'auto_create_btn') {
    $pageId = $_GET['page_id'];
    $btnTitle = $_GET['title'];
    $link = 'page.php?ref=' . encode_id($pageId); // สร้างลิ้งค์แบบเข้ารหัส

    // หา max order เดิม
    $maxOrder = $pdo->query("SELECT MAX(sort_order) FROM sidebar_buttons")->fetchColumn();
    $newOrder = $maxOrder + 1;

    $stmt = $pdo->prepare("INSERT INTO sidebar_buttons (name, link, sort_order) VALUES (?, ?, ?)");
    $stmt->execute([$btnTitle, $link, $newOrder]);

    $message = '<div class="alert alert-success">✓ สร้างปุ่มเมนูและผูกลิ้งค์ให้เรียบร้อยแล้ว!</div>';
}

// ดึงข้อมูลแก้ไข
$editData = null;
if (isset($_GET['edit'])) {
    $editData = $pdo->query("SELECT * FROM custom_pages WHERE id=" . intval($_GET['edit']))->fetch();
}

// ดึงรายการทั้งหมด
$pages = $pdo->query("SELECT * FROM custom_pages ORDER BY updated_at DESC")->fetchAll();
?>

<h2>📝 จัดการหน้าเนื้อหา (Custom Pages)</h2>
<?php echo $message; ?>
<?php echo $popupScript; // แสดง Popup ถ้ามีการบันทึกใหม่ ?>

<div class="admin-form">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h3><?php echo $editData ? 'แก้ไขหน้าเพจ' : 'สร้างหน้าเพจใหม่'; ?></h3>
        <button type="button" onclick="loadTemplate()" class="btn btn-secondary"
            style="background: #64748b; color: white; font-size: 13px;">
            <i class="fas fa-magic"></i> โหลดตัวอย่าง (วิสัยทัศน์)
        </button>
    </div>

    <form method="POST">
        <input type="hidden" name="action" value="save">
        <?php if ($editData): ?><input type="hidden" name="id" value="<?php echo $editData['id']; ?>"><?php endif; ?>

        <div style="margin-bottom: 15px;">
            <label>หัวข้อหน้าเพจ</label>
            <input type="text" name="title" value="<?php echo htmlspecialchars($editData['title'] ?? ''); ?>" required
                placeholder="เช่น ประวัติความเป็นมา">
        </div>

        <div style="margin-bottom: 15px;">
            <label>เนื้อหา (HTML)</label>
            <textarea id="contentArea" name="content" rows="12"
                placeholder="พิมพ์เนื้อหาที่นี่..."><?php echo htmlspecialchars($editData['content'] ?? ''); ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary">💾 บันทึกเนื้อหา</button>
        <?php if ($editData): ?>
            <a href="?page=manage_pages" class="btn btn-secondary">ยกเลิก</a>
        <?php endif; ?>
    </form>
</div>

<div class="card">
    <h3>รายการหน้าที่สร้างแล้ว</h3>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>หัวข้อ</th>
                <th>ลิงก์ (เข้ารหัส)</th>
                <th>จัดการ</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pages as $p): ?>
                <?php $encodedLink = 'page.php?ref=' . encode_id($p['id']); // สร้างลิ้งค์แบบใหม่ ?>
                <tr>
                    <td><?php echo $p['id']; ?></td>
                    <td><?php echo htmlspecialchars($p['title']); ?></td>
                    <td>
                        <div style="display: flex; gap: 5px;">
                            <input type="text" value="<?php echo $encodedLink; ?>" readonly
                                style="width: 100%; background: #f8f9fa; border: 1px solid #ddd; color: var(--primary); font-size: 12px;">
                            <button type="button" class="btn btn-sm btn-secondary"
                                onclick="navigator.clipboard.writeText('<?php echo $encodedLink; ?>'); alert('คัดลอกแล้ว!');">Copy</button>
                        </div>
                    </td>
                    <td>
                        <a href="?page=manage_pages&edit=<?php echo $p['id']; ?>" class="btn btn-sm btn-primary">แก้ไข</a>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('ยืนยันการลบ?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-danger">ลบ</button>
                        </form>
                        <a href="../<?php echo $encodedLink; ?>" target="_blank" class="btn btn-sm btn-secondary">ดู</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
    function loadTemplate() {
        const template = `<div style="max-width: 800px; margin: 0 auto;">
    <div style="text-align: center; padding: 40px 20px; background: linear-gradient(135deg, #fff7ed 0%, #ffffff 100%); border-radius: 15px; margin-bottom: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
        <div style="width: 80px; height: 80px; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; box-shadow: 0 4px 10px rgba(249, 115, 22, 0.2);">
            <i class="fas fa-eye" style="font-size: 36px; color: #f97316;"></i>
        </div>
        <h2 style="color: #c2410c; margin-bottom: 15px; font-size: 24px;">วิสัยทัศน์ (Vision)</h2>
        <p style="font-size: 20px; font-weight: 500; color: #4b5563; line-height: 1.6; max-width: 600px; margin: 0 auto;">
            "เป็นองค์กรชั้นนำด้านสุขภาพ ที่มุ่งมั่นยกระดับคุณภาพชีวิตประชาชน ด้วยนวัตกรรมและการบริการที่เป็นเลิศ ภายในปี 2570"
        </p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
        <div style="background: white; padding: 25px; border-radius: 12px; border: 1px solid #e5e7eb; border-top: 4px solid #f97316;">
            <h3 style="color: #334155; margin-top: 0; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-bullseye" style="color: #f97316;"></i> พันธกิจ (Mission)
            </h3>
            <ul style="padding-left: 20px; color: #64748b; line-height: 1.8; margin-bottom: 0;">
                <li>พัฒนาระบบบริการสุขภาพให้ได้มาตรฐานสากล</li>
                <li>ส่งเสริมการมีส่วนร่วมของชุมชนในการดูแลสุขภาพ</li>
                <li>บริหารจัดการองค์กรด้วยหลักธรรมาภิบาล</li>
                <li>พัฒนาศักยภาพบุคลากรสู่ความเป็นมืออาชีพ</li>
            </ul>
        </div>
        <div style="background: white; padding: 25px; border-radius: 12px; border: 1px solid #e5e7eb; border-top: 4px solid #3b82f6;">
            <h3 style="color: #334155; margin-top: 0; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-heart" style="color: #3b82f6;"></i> ค่านิยม (Core Values)
            </h3>
            <ul style="list-style: none; padding: 0; color: #64748b; line-height: 1.8; margin-bottom: 0;">
                <li style="margin-bottom: 8px;"><strong style="color: #3b82f6;">S</strong> - Service Mind</li>
                <li style="margin-bottom: 8px;"><strong style="color: #3b82f6;">M</strong> - Mastery</li>
                <li style="margin-bottom: 8px;"><strong style="color: #3b82f6;">A</strong> - Agility</li>
                <li style="margin-bottom: 8px;"><strong style="color: #3b82f6;">R</strong> - Responsibility</li>
                <li><strong style="color: #3b82f6;">T</strong> - Teamwork</li>
            </ul>
        </div>
    </div>
</div>`;

        if (confirm('ต้องการแทนที่เนื้อหาเดิมด้วยตัวอย่างใช่หรือไม่?')) {
            document.getElementById('contentArea').value = template;
        }
    }
</script>