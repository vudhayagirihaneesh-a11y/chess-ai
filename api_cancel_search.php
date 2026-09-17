<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success' => false]); exit; }

$host = 'sql100.infinityfree.com';
$dbname = 'if0_41675814_chess';
$user = 'if0_41675814';
$pass = 'Haneesh01';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB error']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$otp = trim($input['otp'] ?? '');
$username = trim($input['username'] ?? '');

if (!$otp || !$username) { http_response_code(400); echo json_encode(['success' => false]); exit; }

$stmt = $db->prepare("DELETE FROM game_rooms WHERE otp = ? AND host_username = ? AND status LIKE 'waiting%'");
$stmt->execute([$otp, $username]);

echo json_encode(['success' => true]);