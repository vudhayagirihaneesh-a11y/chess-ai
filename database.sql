-- Database schema for Chess AI

-- Users Table (Handles authentication and multiplayer Elo rankings)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    elo INT DEFAULT 1200
);

-- Game Rooms Table (Handles Matchmaking, Broadcasts, and Live Game Syncing)
CREATE TABLE IF NOT EXISTS game_rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    otp VARCHAR(10) NOT NULL UNIQUE,
    host_username VARCHAR(255) NOT NULL,
    guest_username VARCHAR(255),
    status VARCHAR(50) DEFAULT 'waiting',
    moves TEXT,
    host_last_ping INT DEFAULT 0,
    guest_last_ping INT DEFAULT 0,
    is_broadcast TINYINT(1) DEFAULT 0
);