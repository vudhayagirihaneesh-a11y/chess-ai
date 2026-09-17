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
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$otp = trim($input['otp'] ?? '');
$lastCount = (int)($input['lastCount'] ?? 0);
$isHost = isset($input['isHost']) ? (bool)$input['isHost'] : null;
$isSpectator = isset($input['isSpectator']) ? (bool)$input['isSpectator'] : false;
if (!$otp) { http_response_code(400); echo json_encode(['success' => false]); exit; }

$stmt = $db->prepare('SELECT moves, status, guest_username, host_last_ping, guest_last_ping FROM game_rooms WHERE otp = ?');
$stmt->execute([$otp]);
$room = $stmt->fetch();
if (!$room) { http_response_code(404); echo json_encode(['success' => false]); exit; }

$now = time();

// Update ping
if ($isHost !== null && !$isSpectator) {
    if ($isHost) {
        $db->prepare("UPDATE game_rooms SET host_last_ping = ? WHERE otp = ?")->execute([$now, $otp]);
        $room['host_last_ping'] = $now;
    } else {
        $db->prepare("UPDATE game_rooms SET guest_last_ping = ? WHERE otp = ?")->execute([$now, $otp]);
        $room['guest_last_ping'] = $now;
    }

    // Check for opponent disconnect during an active match
    if ($room['status'] === 'playing') {
        $opponent_ping = $isHost ? (int)$room['guest_last_ping'] : (int)$room['host_last_ping'];
        // If opponent hasn't pinged in 15 seconds, declare disconnect
        if ($opponent_ping > 0 && ($now - $opponent_ping) > 15) {
            $moves = json_decode($room['moves'], true);
            $dropped_color = $isHost ? 'b' : 'w';
            $moves[] = '__DISCONNECT_' . $dropped_color;
            $db->prepare("UPDATE game_rooms SET moves = ?, status = 'disconnected' WHERE otp = ?")->execute([json_encode($moves), $otp]);
            $room['moves'] = json_encode($moves);
            $room['status'] = 'disconnected';
        }
    }
}

$moves = json_decode($room['moves'], true);
echo json_encode([
    'success' => true,
    'moves' => array_slice($moves, $lastCount),
    'count' => count($moves),
    'status' => $room['status'],
    'guest_username' => $room['guest_username']
]);