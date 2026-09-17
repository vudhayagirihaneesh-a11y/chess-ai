<div align="center">
  <h1>♟️ Chess AI</h1>
  <p><b>A full-featured multiplayer and AI chess platform.</b></p>

  [![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)](https://developer.mozilla.org/en-US/docs/Web/JavaScript)
  [![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net/)
  [![Stockfish](https://img.shields.io/badge/Stockfish_WASM-333333?style=for-the-badge&logo=webassembly&logoColor=white)](https://stockfishchess.org/)
  [![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
</div>

---

## 🌟 Introduction

**Chess AI** is an advanced, lightweight web-based chess application. Play directly against a world-class AI (Stockfish), challenge your friends in private rooms, or enter the global matchmaking queue. 

Designed for low-latency and maximum portability, the backend utilizes standard PHP polling, meaning you can host your own fully-functional multiplayer chess server on practically **any shared hosting provider** (no WebSockets or complex Node.js setups required!).

## ⚡ Core Features

| Feature | Description |
| :--- | :--- |
| 🤖 **Play vs AI** | Battle against the Stockfish engine, natively running in your browser via WebAssembly (`stockfish.wasm`). |
| 🌍 **Global Matchmaking** | Hit "Find Random Match" and instantly connect with another player waiting in the queue. |
| 🤝 **Play with Friends** | Create a private room, generate a unique OTP, and play securely with a friend. |
| 📺 **Live Broadcasting** | Generate a broadcast OTP to allow spectators to watch your matches in real-time. |
| 🏆 **Elo Leaderboards** | Win multiplayer matches to climb the global Elo ranking leaderboard. |

## 🏗️ Architecture

- **Frontend**: HTML5, CSS3, and Vanilla JavaScript. Utilizes `chess.js` for complex move validation and `chessboard.js` for the gorgeous interactive board UI.
- **Backend**: PHP 7.4+ scripts handling room states, matchmaking (`api_matchmake.php`), and move synchronization (`get_moves.php`, `make_move.php`).
- **Database**: A lightweight MySQL schema (`database.sql`) to track users, Elo ratings, and active game rooms.
- **Engine**: The Stockfish chess engine runs entirely client-side using Web Workers, ensuring the backend remains completely stateless and lightning fast.

## 🚀 Setup & Installation

Deploying your own Chess AI server takes less than 5 minutes.

1. **Clone the repository:**
   ```bash
   git clone https://github.com/vudhayagirihaneesh-a11y/chess-ai.git
   cd chess-ai
   ```
2. **Database Setup:**
   - Create a MySQL database.
   - Import the included `database.sql` schema: `mysql -u root -p database_name < database.sql`
   - *(Note: Ensure you update database connection credentials in the PHP files if applicable).*
3. **Host the Files:**
   - Upload the entire directory to your PHP web server (e.g., `public_html` or `htdocs`).
4. **Play:**
   - Navigate to `index.html` (or `signup.html`), create an account, and start playing!

---

<div align="center">
  <i>Checkmate the competition.</i><br>
  <b>Proprietary software. All rights reserved.</b>
</div>
