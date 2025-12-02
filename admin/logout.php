<?php
session_start();

// ลบ Session ทั้งหมด
$_SESSION = array();

// ลบ Session Cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// ปิด Session
session_destroy();

// บันทึกบันทึกกิจกรรม (optional)
try {
    require '../config.php';
    if (isset($pdo)) {
        // บันทึกว่าออกระบบเมื่อไหร่
        // $stmt = $pdo->prepare("INSERT INTO activity_logs (admin_id, action, description, created_at) VALUES (?, 'logout', 'ออกจากระบบ', NOW())");
        // $stmt->execute([$_SESSION['admin_id'] ?? 0]);
    }
} catch (Exception $e) {
    // ไม่มี error ให้แสดง
}

// เปลี่ยนเส้นทางไปหน้าเข้าสู่ระบบ
header('Location: login.php?logout=true');
exit;
?>