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
$move = trim($input['move'] ?? '');
$username = trim($input['username'] ?? '');
if (!$otp || !$move || !$username) { http_response_code(400); echo json_encode(['success' => false]); exit; }

try {
    $db->beginTransaction();
    $stmt = $db->prepare('SELECT * FROM game_rooms WHERE otp = ? FOR UPDATE');
    $stmt->execute([$otp]);
    $room = $stmt->fetch();
    if (!$room) { 
        $db->rollBack();
        http_response_code(404); echo json_encode(['success' => false]); exit; 
    }

    // Verify that the person making the move is an active player (blocks spectators)
    if ($room['host_username'] !== $username && $room['guest_username'] !== $username) {
        $db->rollBack();
        http_response_code(403); echo json_encode(['success' => false, 'message' => 'Not authorized']); exit;
    }

    // Ensure spectators cannot make moves in a broadcast room
    if (!empty($room['is_broadcast']) && $room['host_username'] !== $username) {
        $db->rollBack();
        http_response_code(403); echo json_encode(['success' => false, 'message' => 'Spectators cannot make moves']); exit;
    }

    // Prevent malicious injection of system tokens
    if (strpos($move, '__') === 0) {
        $allowed = ['__RESTART', '__COLOR_w', '__COLOR_b', '__RESIGN_w', '__RESIGN_b'];
        if (!in_array($move, $allowed)) {
            $db->rollBack();
            http_response_code(403); echo json_encode(['success' => false, 'message' => 'Invalid system token']); exit;
        }
        if (strpos($move, '__RESIGN_') === 0) {
            $expectedColor = ($room['host_username'] === $username) ? 'w' : 'b';
            if (substr($move, 9) !== $expectedColor) {
                $db->rollBack();
                http_response_code(403); echo json_encode(['success' => false, 'message' => 'Cannot resign for opponent']); exit;
            }
        } elseif (in_array($move, ['__RESTART', '__COLOR_w', '__COLOR_b'])) {
            if ($room['host_username'] !== $username) {
                $db->rollBack();
                http_response_code(403); echo json_encode(['success' => false, 'message' => 'Only host can modify game state']); exit;
            }
        }
    }

    $moves = json_decode($room['moves'], true);
    $moves[] = $move;
    $stmt = $db->prepare('UPDATE game_rooms SET moves = ? WHERE otp = ?');
    $stmt->execute([json_encode($moves), $otp]);
    $db->commit();
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    http_response_code(500); echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]); exit;
}