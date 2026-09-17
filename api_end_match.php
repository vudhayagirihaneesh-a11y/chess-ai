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
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'DB error']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$otp = trim($input['otp'] ?? '');
$result = trim($input['result'] ?? '');

if (!$otp || !in_array($result, ['w', 'b', 'draw'])) { echo json_encode(['success' => false]); exit; }

// We assume your database holds a 'host_username' column
$stmt = $db->prepare("SELECT host_username, guest_username, status FROM game_rooms WHERE otp = ?");
$stmt->execute([$otp]);
$room = $stmt->fetch();

if ($room) {
    $hostName = $room['host_username'];
    $guestName = $room['guest_username'];
    if (!$hostName || !$guestName) { echo json_encode(['success' => false]); exit; }

    // Atomically try to mark as finished to prevent double Elo calculations
    $stmt = $db->prepare("UPDATE game_rooms SET status = 'finished' WHERE otp = ? AND status != 'finished'");
    $stmt->execute([$otp]);

    // Fetch ratings. If rowCount was 0, it was already finished, so these are the up-to-date post-match ratings.
    $stmt2 = $db->prepare("SELECT username, elo FROM users WHERE username IN (?, ?)");
    $stmt2->execute([$hostName, $guestName]);
    $users = $stmt2->fetchAll();
    $elos = [];
    foreach($users as $u) { $elos[$u['username']] = (int)$u['elo']; }

    $eloH = $elos[$hostName] ?? 1200;
    $eloG = $elos[$guestName] ?? 1200;

    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => true, 'newHost' => $eloH, 'newGuest' => $eloG]);
        exit;
    }

    // Elo Math (K-Factor 32)
    $rH = pow(10, $eloH / 400);
    $rG = pow(10, $eloG / 400);
    $eH = $rH / ($rH + $rG);
    $eG = $rG / ($rH + $rG);

    $sH = ($result === 'w') ? 1 : (($result === 'b') ? 0 : 0.5);
    $sG = ($result === 'b') ? 1 : (($result === 'w') ? 0 : 0.5);

    $newH = round($eloH + 32 * ($sH - $eH));
    $newG = round($eloG + 32 * ($sG - $eG));

    $db->prepare("UPDATE users SET elo = ? WHERE username = ?")->execute([$newH, $hostName]);
    $db->prepare("UPDATE users SET elo = ? WHERE username = ?")->execute([$newG, $guestName]);

    echo json_encode(['success' => true, 'newHost' => $newH, 'newGuest' => $newG]);
} else {
    echo json_encode(['success' => false, 'message' => 'Room not found']);
}