# 🎲 666Win — Real-Time Interactive Betting & Gaming Platform

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-API-FF2D20?style=for-the-badge&logo=laravel" alt="Laravel">
  <img src="https://img.shields.io/badge/Vue-3.x-4FC08D?style=for-the-badge&logo=vuedotjs" alt="Vue 3">
  <img src="https://img.shields.io/badge/Pixi.js-WebGL%202D-E72264?style=for-the-badge&logo=pixijs" alt="Pixi.js">
  <img src="https://img.shields.io/badge/Matter.js-2D%20Physics-blue?style=for-the-badge" alt="Matter.js">
  <img src="https://img.shields.io/badge/WebSockets-Realtime-orange?style=for-the-badge&logo=pusher" alt="WebSockets">
</p>

---

## 📖 What is 666Win?

**666Win** is a high-performance **real-time interactive betting and prediction gaming platform** featuring a decoupled architecture: an event-driven **Laravel API backend** and a hardware-accelerated **Vue 3 WebGL frontend**.

Equipped with **Pixi.js** for high-framerate 60 FPS 2D canvas rendering and **Matter-js** for realistic physics simulation, 666Win delivers ultra-responsive game rounds, provably fair mechanics, real-time odds updates via WebSockets, and instant wallet credit settlement.

---

## 🎯 Why 666Win? Problem & Solution

| The Challenge | How 666Win Solves It |
| :--- | :--- |
| **High Latency in Live Betting:** Traditional betting sites require polling, resulting in delayed odds and missed bet placements during live action. | Utilizes **Laravel Echo and Pusher WebSockets** for bi-directional, sub-second event broadcasts, ensuring odds and game rounds update instantaneously across all connected clients. |
| **Heavy, Resource-Intensive Game Renders:** Complex 3D game engines cause lag, high battery consumption, and crashes on budget mobile devices. | Built on **Pixi.js (WebGL/Canvas) and Matter-js (Physics Engine)**, offering lightweight, buttery-smooth 60 FPS animations that load instantly even on low-end hardware. |
| **Player Trust & RNG Manipulation Concerns:** Players demand verification that match outcomes and game drops are fair. | Implements a **Provably Fair RNG (Random Number Generator)** seed verification architecture, enabling cryptographic validation of every round's outcome. |

---

## 🌟 Key Features

### 🎮 Hardware-Accelerated Interactive Gaming
- **Physics-Driven Mini Games:** Realistic collision, bounce, and trajectory calculations powered by **Matter-js**.
- **60 FPS Canvas Rendering:** High-performance 2D graphics, particle systems, and win celebrations rendered via **Pixi.js WebGL**.
- **Instant Bet Placement:** Micro-bets, auto-betting sequences, and multiplier cashouts with zero interface freezing.

### ⚡ Real-Time Multiplayer Experience
- **Live Community Bet Ticker:** Real-time feed showing bets placed and big wins scored by other active online players.
- **WebSocket Game Synchronization:** Every round starts and settles simultaneously across all connected player sessions.
- **Dynamic Odds Computation:** Real-time risk and payout calculation adapting to active platform betting volume.

### 💰 Wallet & Financial Architecture
- **Multi-Currency Player Wallets:** Support for platform credits, fiat deposits, and cryptocurrency transactions.
- **Double-Entry Ledger:** Ensures mathematical accuracy with immutable transaction logs for every bet, refund, win, and withdrawal.
- **Instant Payout Processing:** Automated and manual withdrawal pipelines with player identity verification safeguards.

### 🛡️ Administrative Command Suite
- **Live Game Moderation:** Monitor active player counts, total daily gross gaming revenue (GGR), and platform liabilities.
- **Risk Management & House Edge:** Adjust house margins, maximum bet limits, and multiplier caps across different game modes.
- **Player Oversight & Anti-Fraud:** Review player velocity, detect duplicate IPs or suspicious betting patterns, and manage user statuses.

---

## 🛠️ Technical Stack

### Frontend (`client/`)
- **Framework:** Vue 3.5 (Composition API)
- **State Management:** Pinia 3.x
- **2D WebGL Graphics:** Pixi.js 8.x
- **2D Physics Engine:** Matter-js
- **Real-Time Client:** Laravel Echo, Pusher JS
- **Styling & PWA:** TailwindCSS v4, Vite PWA Plugin, Lucide Icons

### Backend (`api/`)
- **Framework:** Laravel 11.x (PHP 8.2+)
- **Database:** MySQL 8.0+ / MariaDB (High concurrency transactions)
- **Real-Time Broadcasting:** WebSockets / Pusher / Redis PubSub
- **Security:** VAPID Web Push notifications, Rate Limiting, Sanitized Request Validation

---

## 📦 Project Structure

```
666Win/
├── api/                       ← Laravel RESTful API & WebSocket Broadcasting Server
│   ├── app/                   ← Controllers, Models, Events, Jobs
│   ├── config/                ← Broadcasting, database, and cache config
│   ├── routes/                ← Web, API, and Channels routes
│   └── database/              ← Migrations, Seeders, and Provably Fair RNG logic
│
└── client/                    ← Vue 3 + Pixi.js WebGL Interactive Client
    ├── src/
    │   ├── components/        ← Game canvas, bet slips, leaderboards
    │   ├── views/             ← Casino, Sports, Dashboard, Wallet
    │   ├── stores/            ← Pinia stores (Auth, Wallet, GameState)
    │   └── assets/            ← Sprites, sound effects, particle textures
    ├── package.json           ← Frontend dependencies
    └── vite.config.js         ← Vite build & PWA configuration
```

---

## 🚀 Local Installation & Setup

### 1. Backend API Setup
```bash
cd api
composer install
cp .env.example .env
php artisan key:generate

# Configure your database & broadcasting in .env
php artisan migrate --seed

# Start backend server
php artisan serve
```

### 2. Frontend Client Setup
```bash
cd client
npm install

# Start development client
npm run dev
```
Open [http://localhost:5173](http://localhost:5173) in your browser.

---

## 👨‍💻 Author
**Rana Umair Nawaz**  
- [GitHub Profile](https://github.com/ranaumairnawaz)  
- [LinkedIn Profile](https://linkedin.com/in/ranaumairnawaz)
