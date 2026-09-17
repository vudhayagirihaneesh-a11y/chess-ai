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
    try { $db->exec("ALTER TABLE game_rooms ADD COLUMN host_username VARCHAR(255)"); } catch (Exception $e) {}
    try { $db->exec("ALTER TABLE game_rooms ADD COLUMN host_last_ping INT DEFAULT 0, ADD COLUMN guest_last_ping INT DEFAULT 0"); } catch (Exception $e) {}
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$username = $input['username'] ?? '';
if (!$username) { echo json_encode(['success' => false]); exit; }

// Check if already in queue
$stmt = $db->prepare("SELECT otp FROM game_rooms WHERE host_username = ? AND status = 'waiting_random'");
$stmt->execute([$username]);
$myRoom = $stmt->fetch();
if ($myRoom) {
    echo json_encode(['status' => 'waiting', 'otp' => $myRoom['otp']]);
    exit;
}

// Try to find someone waiting whose connection is still active (pinged in last 15s)
$stmt = $db->prepare("SELECT otp, host_username FROM game_rooms WHERE status = 'waiting_random' AND host_username != ? AND host_last_ping > ? LIMIT 1");
$stmt->execute([$username, time() - 15]);
$room = $stmt->fetch();

if ($room) {
    // Join their room safely (prevent concurrent overwrites)
    $stmt = $db->prepare("UPDATE game_rooms SET guest_username = ?, status = 'playing', guest_last_ping = ? WHERE otp = ? AND status = 'waiting_random'");
    $stmt->execute([$username, time(), $room['otp']]);
    if ($stmt->rowCount() > 0) {
        echo json_encode(['status' => 'joined', 'otp' => $room['otp'], 'color' => 'b', 'opponent' => $room['host_username']]);
        exit;
    }
}

// Create new queue room (Fallback if no room or race condition missed it)
$otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$stmt = $db->prepare("INSERT INTO game_rooms (otp, host_username, status, moves, host_last_ping) VALUES (?, ?, 'waiting_random', '[]', ?)");
$stmt->execute([$otp, $username, time()]);
echo json_encode(['status' => 'waiting', 'otp' => $otp]);