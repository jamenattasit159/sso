<?php
// config.php - ตั้งค่าฐานข้อมูล

// ตั้งค่า Timezone
date_default_timezone_set('Asia/Bangkok');

// ตั้งค่าฐานข้อมูล
$host = 'localhost';
$db = 'hospital_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

// DSN
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

try {
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("ข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล: " . $e->getMessage());
}

// ฟังก์ชันช่วยเหลือ
function sanitize($data)
{
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

function redirect($url)
{
    header("Location: $url");
    exit;
}

function base_url($path = '')
{
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $base = "$protocol://$host" . dirname($_SERVER['PHP_SELF']);
    return $base . '/' . ltrim($path, '/');
}
?>