<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$host = 'sql100.infinityfree.com';
$dbname = 'if0_41675814_chess';
$user = 'if0_41675814';
$pass = 'Haneesh01';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    // Ensure the elo column exists
    try { $db->exec("ALTER TABLE users ADD COLUMN elo INT DEFAULT 1200"); } catch (Exception $e) {}
} catch (PDOException $e) {
    echo json_encode(['elo' => 1200]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$username = $input['username'] ?? '';
if (!$username) { echo json_encode(['elo' => 1200]); exit; }

$stmt = $db->prepare("SELECT elo FROM users WHERE username = ?");
$stmt->execute([$username]);
$u = $stmt->fetch();
echo json_encode(['elo' => $u && isset($u['elo']) ? (int)$u['elo'] : 1200]);