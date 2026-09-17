<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$host = 'sql100.infinityfree.com';
$dbname = 'if0_41675814_chess';
$user = 'if0_41675814';
$pass = 'Haneesh01';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    // Ensure the elo column exists before querying
    try { $db->exec("ALTER TABLE users ADD COLUMN elo INT DEFAULT 1200"); } catch (Exception $e) {}
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'DB error']);
    exit;
}

try {
    // Get top 10 highest rated users
    $stmt = $db->query("SELECT username, elo FROM users ORDER BY elo DESC LIMIT 10");
    $leaderboard = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'leaderboard' => $leaderboard]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Query error']);
}