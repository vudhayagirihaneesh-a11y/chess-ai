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
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$otp = trim($input['otp'] ?? '');
$username = trim($input['username'] ?? '');
$isSpectator = $input['isSpectator'] ?? false;

if (!$otp || !$username) { http_response_code(400); echo json_encode(['success' => false]); exit; }

$stmt = $db->prepare('SELECT * FROM game_rooms WHERE otp = ?');
$stmt->execute([$otp]);
$room = $stmt->fetch();

if (!$room) { http_response_code(404); echo json_encode(['success' => false, 'message' => 'Room not found']); exit; }

// Ensure the user is joining the correct type of room
$isBroadcastRoom = !empty($room['is_broadcast']);
if ($isSpectator && !$isBroadcastRoom) {
    http_response_code(400); echo json_encode(['success' => false, 'message' => 'This OTP is for a friend match. Please use the "Join Room" button instead.']); exit;
}
if (!$isSpectator && $isBroadcastRoom) {
    http_response_code(400); echo json_encode(['success' => false, 'message' => 'This OTP is for a broadcast. Please use the "Watch Broadcast" button instead.']); exit;
}

// Prevent actual players from joining an already started room
if (!$isSpectator && $room['status'] !== 'waiting') { http_response_code(400); echo json_encode(['success' => false, 'message' => 'Room already started']); exit; }

if ($isSpectator) {
    // Only update guest_username for broadcasts (where status remains 'waiting') so we don't overwrite a real opponent
    if ($room['status'] === 'waiting') {
        $stmt = $db->prepare('UPDATE game_rooms SET guest_username = ? WHERE otp = ?');
        $stmt->execute([$username, $otp]);
    }
    echo json_encode(['success' => true, 'host' => $room['host_username']]);
} else {
    $stmt = $db->prepare('UPDATE game_rooms SET guest_username = ?, status = \'playing\', guest_last_ping = ? WHERE otp = ? AND status = \'waiting\'');
    $stmt->execute([$username, time(), $otp]);
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'host' => $room['host_username']]);
    } else {
        http_response_code(400); 
        echo json_encode(['success' => false, 'message' => 'Room already started or unavailable']);
    }
}