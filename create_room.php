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
    // Ensure ping columns exist
    try { $db->exec("ALTER TABLE game_rooms ADD COLUMN host_last_ping INT DEFAULT 0, ADD COLUMN guest_last_ping INT DEFAULT 0"); } catch (Exception $e) {}
    try { $db->exec("ALTER TABLE game_rooms ADD COLUMN is_broadcast TINYINT(1) DEFAULT 0"); } catch (Exception $e) {}
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$username = trim($input['username'] ?? '');
if (!$username) { http_response_code(400); echo json_encode(['success' => false]); exit; }

$isBroadcast = !empty($input['isBroadcast']) ? 1 : 0;

$otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$stmt = $db->prepare('INSERT INTO game_rooms (otp, host_username, moves, status, host_last_ping, is_broadcast) VALUES (?, ?, \'[]\', \'waiting\', ?, ?)');
$stmt->execute([$otp, $username, time(), $isBroadcast]);
echo json_encode(['success' => true, 'otp' => $otp]);