# 🎮 Bet4Gain — Online Multiplayer Crash Game

## Complete Development Plan & Architecture Blueprint

> **Goal:** Build a production-ready, Codecanyon-quality online multiplayer crash (Aviator-style) betting game using Laravel, Vue 3, Tailwind CSS, Pinia, and Laravel Reverb.

---

## Table of Contents

1. [Project Overview](#1-project-overview)
2. [Tech Stack](#2-tech-stack)
3. [Architecture Overview](#3-architecture-overview)
4. [Database Schema](#4-database-schema)
5. [Development Phases](#5-development-phases)
6. [Feature Breakdown](#6-feature-breakdown)
7. [Game Engine Design](#7-game-engine-design)
8. [WebSocket Events Map](#8-websocket-events-map)
9. [API Endpoints](#9-api-endpoints)
10. [Security & Anti-Cheat](#10-security--anti-cheat)
11. [Codecanyon Submission Checklist](#11-codecanyon-submission-checklist)
12. [Folder Structure](#12-folder-structure)
13. [Timeline Estimate](#13-timeline-estimate)

---

## 1. Project Overview

**Bet4Gain** is a real-time multiplayer crash/aviator betting game where players place bets before a round starts, watch a multiplier climb on a canvas-animated curve, and must cash out before the multiplier "crashes." The game is synchronized across all connected players via WebSockets (Laravel Reverb).

### Key Selling Points (for Codecanyon)

- **Provably Fair** — Full cryptographic verification (server seed + client seed + nonce)
- **Real-time Multiplayer** — All players see the same game state simultaneously
- **Dual Currency** — Real wallet (NGN) + virtual coins for gameplay
- **Guest Play** — Try before you sign up
- **Mobile-First Responsive** — Plays beautifully on any device
- **Dark/Light Theme** — User-togglable, system-preference aware
- **Customizable Flying Object** — Admin can upload/replace animated sprites
- **Built-in Payment Gateways** — Paystack + Nomba
- **Admin Panel** — Filament-powered admin dashboard with separate theme
- **Live Chat + Leaderboard** — Community engagement built-in
- **Ad System** — Admin-managed advertisement placements
- **Auto Cashout** — Set target multiplier, auto-cash on reach
- **Anti-Cheat & Rate Limiting** — Production-grade security

---

## 2. Tech Stack

| Layer                | Technology                  | Purpose                                              |
| -------------------- | --------------------------- | ---------------------------------------------------- |
| **Backend**          | Laravel 11+                 | API, game logic, auth, queues                        |
| **Frontend**         | Blade + Vue 3 (SFC)         | Hybrid rendering, SPA-like islands                   |
| **State Management** | Pinia                       | Client-side game/user state                          |
| **CSS Framework**    | Tailwind CSS 3              | Utility-first responsive styling                     |
| **WebSockets**       | Laravel Reverb              | Real-time game sync, chat, presence                  |
| **Game Rendering**   | HTML5 Canvas + RAF          | Smooth 60fps game animation                          |
| **Admin Panel**      | Filament PHP 3              | Admin dashboard & management                         |
| **Auth**             | Laravel Fortify + Socialite | Email/password + social login (Google, GitHub, etc.) |
| **Payments**         | Paystack + Nomba APIs       | Deposits & withdrawals (NGN)                         |
| **Queue**            | Laravel Queue (Redis/DB)    | Game rounds, payouts, notifications                  |
| **Cache**            | Redis                       | Leaderboard, online users, rate limiting             |
| **Database**         | MySQL 8+                    | Primary data store                                   |
| **Build Tool**       | Vite                        | Asset bundling & HMR                                 |

---

## 3. Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│                        CLIENT (Browser)                      │
│                                                               │
│  ┌──────────┐  ┌──────────────┐  ┌─────────────────────────┐ │
│  │  Vue 3    │  │  Pinia Store │  │  Canvas Game Engine     │ │
│  │  Components│  │  - game      │  │  - Curve renderer       │ │
│  │  - BetPanel│  │  - user      │  │  - Flying object sprite │ │
│  │  - Chat   │  │  - chat      │  │  - Multiplier display   │ │
│  │  - Board  │  │  - wallet    │  │  - RAF loop             │ │
│  └─────┬─────┘  └──────┬───────┘  └────────────┬────────────┘ │
│        │               │                        │              │
│        └───────────────┼────────────────────────┘              │
│                        │                                       │
│              ┌─────────▼──────────┐                           │
│              │  Laravel Echo +     │                           │
│              │  Reverb Client      │                           │
│              └─────────┬──────────┘                           │
└────────────────────────┼──────────────────────────────────────┘
                         │ WebSocket
                         │
┌────────────────────────┼──────────────────────────────────────┐
│                        │        SERVER (Laravel)               │
│              ┌─────────▼──────────┐                           │
│              │  Laravel Reverb     │                           │
│              │  WebSocket Server   │                           │
│              └─────────┬──────────┘                           │
│                        │                                       │
│  ┌─────────────────────▼──────────────────────────────────┐   │
│  │               Game Engine Service                       │   │
│  │  - Round lifecycle (betting → running → crashed)        │   │
│  │  - Provably fair crash point calculation                │   │
│  │  - Auto-cashout processing                              │   │
│  │  - Bet validation & payout                              │   │
│  └─────────────┬──────────────────────────┬───────────────┘   │
│                │                          │                    │
│  ┌─────────────▼────────┐  ┌──────────────▼───────────────┐   │
│  │  Laravel Queue        │  │  Redis Cache                  │   │
│  │  - ProcessGameRound   │  │  - Online users               │   │
│  │  - ProcessPayout      │  │  - Leaderboard                │   │
│  │  - SendNotification   │  │  - Rate limiting              │   │
│  └──────────────────────┘  │  - Game state                  │   │
│                             └───────────────────────────────┘   │
│                                                                 │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │                    MySQL Database                         │   │
│  │  users, wallets, coin_balances, bets, game_rounds,       │   │
│  │  transactions, chat_messages, leaderboard, ads, settings  │   │
│  └──────────────────────────────────────────────────────────┘   │
│                                                                 │
│  ┌──────────────────────┐  ┌────────────────────────────────┐   │
│  │  Filament Admin       │  │  Payment Gateways              │   │
│  │  (Separate Theme)     │  │  - Paystack API                │   │
│  │  /admin/*             │  │  - Nomba API                   │   │
│  └──────────────────────┘  └────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
```

---

## 4. Database Schema

### Core Tables

```
users
├── id (bigint, PK)
├── username (string, unique)
├── email (string, unique)
├── password (string, nullable for social)
├── avatar (string, nullable)
├── provider (string, nullable — google/github)
├── provider_id (string, nullable)
├── is_guest (boolean, default: false)
├── guest_token (string, nullable, unique)
├── is_banned (boolean, default: false)
├── role (enum: user/admin/moderator)
├── email_verified_at (timestamp, nullable)
├── last_seen_at (timestamp, nullable)
├── settings (json — theme preference, sound, etc.)
├── created_at / updated_at
└── deleted_at (soft delete)

wallets
├── id (bigint, PK)
├── user_id (bigint, FK → users)
├── balance (decimal 16,2 — real NGN balance)
├── currency (string, default: 'NGN')
├── is_locked (boolean, default: false)
├── created_at / updated_at

coin_balances
├── id (bigint, PK)
├── user_id (bigint, FK → users)
├── balance (decimal 16,4 — virtual coins)
├── demo_balance (decimal 16,4 — for guest/demo play)
├── created_at / updated_at

transactions
├── id (bigint, PK)
├── user_id (bigint, FK → users)
├── type (enum: deposit/withdrawal/purchase_coins/sell_coins/bet/win/refund/bonus)
├── amount (decimal 16,4)
├── currency (enum: NGN/COINS)
├── reference (string, unique)
├── gateway (string, nullable — paystack/nomba)
├── gateway_reference (string, nullable)
├── status (enum: pending/completed/failed/reversed)
├── metadata (json, nullable)
├── description (string, nullable)
├── created_at / updated_at

game_rounds
├── id (bigint, PK)
├── round_hash (string, unique — provably fair hash)
├── server_seed (string)
├── client_seed (string, nullable)
├── nonce (integer)
├── crash_point (decimal 10,4)
├── status (enum: waiting/betting/running/crashed)
├── started_at (timestamp, nullable)
├── crashed_at (timestamp, nullable)
├── duration_ms (integer, nullable)
├── created_at / updated_at

bets
├── id (bigint, PK)
├── user_id (bigint, FK → users)
├── game_round_id (bigint, FK → game_rounds)
├── amount (decimal 16,4)
├── currency (enum: COINS/DEMO)
├── auto_cashout_at (decimal 10,4, nullable)
├── cashed_out_at (decimal 10,4, nullable)
├── payout (decimal 16,4, nullable)
├── is_auto (boolean, default: false — was it auto-cashout?)
├── status (enum: pending/active/won/lost/cancelled)
├── created_at / updated_at

provably_fair_seeds
├── id (bigint, PK)
├── user_id (bigint, FK → users, nullable)
├── server_seed (string)
├── server_seed_hash (string)
├── client_seed (string)
├── nonce (integer, default: 0)
├── is_active (boolean, default: true)
├── revealed_at (timestamp, nullable)
├── created_at / updated_at

chat_messages
├── id (bigint, PK)
├── user_id (bigint, FK → users)
├── message (text)
├── type (enum: text/system/gif/emoji)
├── is_deleted (boolean, default: false)
├── deleted_by (bigint, FK → users, nullable)
├── created_at

leaderboard_entries (materialized/cached)
├── id (bigint, PK)
├── user_id (bigint, FK → users)
├── period (enum: daily/weekly/monthly/alltime)
├── total_wagered (decimal 16,4)
├── total_won (decimal 16,4)
├── total_profit (decimal 16,4)
├── best_multiplier (decimal 10,4)
├── total_games (integer)
├── win_count (integer)
├── calculated_at (timestamp)

advertisements
├── id (bigint, PK)
├── title (string)
├── image (string)
├── url (string, nullable)
├── placement (enum: sidebar/banner/popup/between_rounds)
├── is_active (boolean, default: true)
├── impressions (integer, default: 0)
├── clicks (integer, default: 0)
├── starts_at (timestamp, nullable)
├── ends_at (timestamp, nullable)
├── priority (integer, default: 0)
├── created_at / updated_at

site_settings
├── id (bigint, PK)
├── key (string, unique)
├── value (text, nullable)
├── type (enum: string/integer/boolean/json/file)
├── group (string — general/game/payment/appearance)
├── created_at / updated_at

game_settings (stored in site_settings, listed here logically)
├── min_bet_amount
├── max_bet_amount
├── max_payout_multiplier
├── betting_duration_seconds (default: 10)
├── house_edge_percent (default: 3)
├── flying_object_sprite (file path)
├── flying_object_type (enum: rocket/plane/custom)
├── background_theme
├── demo_starting_balance (default: 10000)
├── coin_to_ngn_rate
├── ngn_to_coin_rate
├── min_deposit
├── max_deposit
├── min_withdrawal
├── max_withdrawal

notifications
├── id (uuid, PK)
├── user_id (bigint, FK → users)
├── type (string)
├── title (string)
├── message (text)
├── data (json, nullable)
├── read_at (timestamp, nullable)
├── created_at

auto_bet_configs
├── id (bigint, PK)
├── user_id (bigint, FK → users)
├── bet_amount (decimal 16,4)
├── auto_cashout_at (decimal 10,4)
├── stop_on_loss (decimal 16,4, nullable)
├── stop_on_profit (decimal 16,4, nullable)
├── increase_on_loss_percent (decimal 5,2, nullable)
├── increase_on_win_percent (decimal 5,2, nullable)
├── max_rounds (integer, nullable)
├── is_active (boolean, default: false)
├── created_at / updated_at
```

---

## 5. Development Phases

---

### 📦 PHASE 1: Project Foundation & Infrastructure (Week 1-2)

**Goal:** Set up the development environment, install dependencies, configure services, and establish coding standards.

#### Tasks:

| #    | Task                                 | Details                                                                                          |
| ---- | ------------------------------------ | ------------------------------------------------------------------------------------------------ |
| 1.1  | **Laravel Project Setup**            | Fresh Laravel 11, configure `.env`, timezone, locale                                             |
| 1.2  | **Install & Configure Tailwind CSS** | Install via Vite, set up `tailwind.config.js` with custom theme colors, dark mode class strategy |
| 1.3  | **Install & Configure Vue 3**        | `@vitejs/plugin-vue`, set up Vue entry point, configure Blade `<div id="app">`                   |
| 1.4  | **Install & Configure Pinia**        | Set up stores directory, create initial store structure                                          |
| 1.5  | **Install Laravel Reverb**           | `php artisan install:broadcasting`, configure Reverb, test echo                                  |
| 1.6  | **Install Laravel Fortify**          | Configure registration, login, email verification, password reset                                |
| 1.7  | **Install Laravel Socialite**        | Configure Google + GitHub OAuth providers                                                        |
| 1.8  | **Install Filament PHP**             | Set up admin panel at `/admin`, configure guard, custom theme                                    |
| 1.9  | **Install Redis**                    | Configure cache, queue, session drivers                                                          |
| 1.10 | **Database Migrations**              | Create all tables from schema above                                                              |
| 1.11 | **Model Factories & Seeders**        | Create factories for all models, initial seeder with admin user                                  |
| 1.12 | **Base Layout**                      | Create master Blade layout with Vue mounting, Tailwind, dark/light toggle                        |
| 1.13 | **CI/CD Setup**                      | PHPUnit config, Pint for linting, GitHub Actions (optional)                                      |
| 1.14 | **Documentation Skeleton**           | README, INSTALL.md, CHANGELOG.md for Codecanyon                                                  |

**Deliverable:** Running Laravel app with all packages installed, database migrated, dark/light theme toggle working, Reverb broadcasting, Filament admin accessible.

---

### 🎨 PHASE 2: UI/UX Design & Layout System (Week 2-3)

**Goal:** Build the complete responsive layout system, component library, and page shells.

#### Tasks:

| #    | Task                       | Details                                                                                                                                            |
| ---- | -------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------- |
| 2.1  | **Design System**          | Define color palette (primary, accent, success, danger), typography scale, spacing, border radius tokens in Tailwind config                        |
| 2.2  | **Dark/Light Mode System** | CSS variables + Tailwind dark: prefix, user preference persistence (localStorage + DB), system preference detection                                |
| 2.3  | **Main Game Layout**       | 3-column responsive layout: Left (chat/history), Center (game canvas + bet panel), Right (leaderboard/bets) — collapses to single column on mobile |
| 2.4  | **Navigation Component**   | Top nav: logo, wallet balance, coin balance, user avatar dropdown, theme toggle, notifications bell                                                |
| 2.5  | **Mobile Bottom Nav**      | Tab bar: Game, Chat, Leaderboard, Wallet, Profile                                                                                                  |
| 2.6  | **Vue Component Library**  | Build reusable components: `BaseButton`, `BaseInput`, `BaseModal`, `BaseCard`, `BaseToast`, `BaseDropdown`, `BaseTabs`, `BaseAvatar`, `BaseBadge`  |
| 2.7  | **Loading States**         | Skeleton loaders, spinners, pulse animations for all async content                                                                                 |
| 2.8  | **Toast/Notification UI**  | Slide-in toast system for wins, losses, deposits, errors                                                                                           |
| 2.9  | **Responsive Breakpoints** | Test and fine-tune: mobile (< 640px), tablet (640-1024px), desktop (> 1024px)                                                                      |
| 2.10 | **Ad Placement Slots**     | Design ad container components for sidebar, banner, popup, between-rounds                                                                          |
| 2.11 | **Sound System**           | Audio manager: bet placed, cash out, crash, win, chat message (togglable)                                                                          |

**Deliverable:** Complete responsive layout with all page shells, component library, theme system, and empty content areas ready for features.

---

### 🔐 PHASE 3: Authentication & User System (Week 3-4)

**Goal:** Complete auth flow including guest play, social login, and user profiles.

#### Tasks:

| #    | Task                              | Details                                                                                                                                    |
| ---- | --------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------ |
| 3.1  | **Registration Page**             | Vue form: username, email, password, confirm. Inline validation.                                                                           |
| 3.2  | **Login Page**                    | Vue form: email/username + password. Remember me.                                                                                          |
| 3.3  | **Social Login Buttons**          | Google + GitHub OAuth via Socialite. Auto-create account.                                                                                  |
| 3.4  | **Guest Play System**             | Generate guest token (UUID), create temporary user, store in cookie. Guest gets demo coins only. Prompt to register to deposit real money. |
| 3.5  | **Guest → Registered Conversion** | Merge guest betting history when they register. Keep stats.                                                                                |
| 3.6  | **Email Verification**            | Fortify email verification flow with custom Vue page                                                                                       |
| 3.7  | **Password Reset**                | Forgot password flow with custom Vue pages                                                                                                 |
| 3.8  | **User Profile Page**             | Avatar upload, username edit, change password, theme preference, sound settings, client seed management                                    |
| 3.9  | **Online Presence System**        | Reverb presence channel, track online users count + list, update `last_seen_at`                                                            |
| 3.10 | **User Settings Store (Pinia)**   | `useUserStore` — auth state, profile, preferences, online status                                                                           |

**Deliverable:** Full auth system with guest play, social login, user profiles, and real-time online presence.

---

### 🎮 PHASE 4: Game Engine — Core (Week 4-6)

**Goal:** Build the provably fair game engine, canvas renderer, and real-time game loop.

#### Tasks:

| #    | Task                               | Details                                                                                                                        |
| ---- | ---------------------------------- | ------------------------------------------------------------------------------------------------------------------------------ |
| 4.1  | **Provably Fair Engine (Backend)** | Implement crash point calculation: `HMAC_SHA256(server_seed, client_seed + nonce)` → deterministic crash point with house edge |
| 4.2  | **Seed Management**                | Generate server seed chain (hash chain for future rounds), client seed input, seed rotation, reveal previous seeds             |
| 4.3  | **Game Round Lifecycle (Backend)** | State machine: `WAITING → BETTING → RUNNING → CRASHED → WAITING` with configurable timers                                      |
| 4.4  | **Round Scheduler**                | Laravel Queue job / Artisan command that continuously runs rounds. `ProcessGameRound` job manages the full lifecycle.          |
| 4.5  | **Canvas Game Renderer**           | Vue component wrapping HTML5 Canvas: draw curve, multiplier text, flying object, background gradient, crash explosion          |
| 4.6  | **Flying Object System**           | Sprite-based animated object that follows the curve. Admin-uploadable. Default: rocket. Support: PNG, GIF, Lottie JSON         |
| 4.7  | **RAF Animation Loop**             | `requestAnimationFrame` loop synced to server multiplier via WebSocket. Smooth interpolation between server ticks.             |
| 4.8  | **Multiplier Curve Math**          | Exponential curve: `multiplier = 1.0 * e^(0.06 * elapsedTimeMs / 1000)`. Synced with server's authoritative multiplier.        |
| 4.9  | **Game State Sync (Reverb)**       | Broadcast current multiplier at 10-20Hz. Client interpolates between ticks for 60fps smoothness.                               |
| 4.10 | **Crash Animation**                | Explosion/shatter animation when game crashes. Screen shake. Red flash.                                                        |
| 4.11 | **Game History Rail**              | Show last 20-50 crash points as colored pills (green > 2x, yellow 1.5-2x, red < 1.5x)                                          |
| 4.12 | **Game Store (Pinia)**             | `useGameStore` — current round, multiplier, status, history, connected players count                                           |
| 4.13 | **Canvas Responsiveness**          | Dynamic canvas resize on viewport change. Retina/HiDPI support with `devicePixelRatio`.                                        |
| 4.14 | **Background Themes**              | Starfield, sky, grid — admin-selectable background for canvas                                                                  |

**Deliverable:** Fully functional game engine with provably fair crash points, smooth canvas animation, WebSocket sync, and responsive rendering.

---

### 💰 PHASE 5: Betting System (Week 6-7)

**Goal:** Implement the complete betting flow — place bets, cash out, auto-cashout, auto-betting.

#### Tasks:

| #    | Task                        | Details                                                                                                                                    |
| ---- | --------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------ |
| 5.1  | **Bet Panel Component**     | Vue component: bet amount input (with quick amounts: ×2, ½, min, max), place bet button, cash out button (shows current potential win)     |
| 5.2  | **Place Bet (Backend)**     | API endpoint: validate amount, check balance, deduct coins, create bet record, broadcast to all players                                    |
| 5.3  | **Manual Cash Out**         | API endpoint: validate round is running, validate user has active bet, calculate payout at current multiplier, credit coins, broadcast     |
| 5.4  | **Auto Cash Out**           | Player sets target multiplier. Server checks every tick: if multiplier >= target, auto-cash. Race condition protection with mutex/lock.    |
| 5.5  | **Auto Bet Configuration**  | UI panel: enable auto-bet, set base amount, auto-cashout target, stop loss, stop profit, increase on loss %, increase on win %, max rounds |
| 5.6  | **Auto Bet Engine**         | After each round, if auto-bet active: calculate next bet amount based on config, auto-place for next round                                 |
| 5.7  | **Two-Bet Support**         | Allow 2 simultaneous bets per round (like Aviator). Independent cash-out for each.                                                         |
| 5.8  | **Live Bets Panel**         | Show all current round bets: username, amount, status (waiting/cashed @2.5x/busted). Green for wins, red for losses.                       |
| 5.9  | **Bet Validation & Limits** | Min/max bet, max payout, max active bets, balance check, round status check, timing window check                                           |
| 5.10 | **Bet Store (Pinia)**       | `useBetStore` — active bets, bet history, auto-bet config, place/cashout actions                                                           |
| 5.11 | **Demo/Guest Betting**      | Guests bet with demo coins. Same UI, no real money. Banner prompting registration.                                                         |
| 5.12 | **Bet Sound Effects**       | Distinct sounds: bet placed (coin clink), cash out (cha-ching), busted (crash)                                                             |

**Deliverable:** Complete betting system with manual/auto cashout, auto-betting strategy, two-bet support, and real-time broadcast of all bets.

---

### 💳 PHASE 6: Wallet & Payment System (Week 7-8)

**Goal:** Implement deposits, withdrawals, coin purchase/sell, and transaction history.

#### Tasks:

| #    | Task                      | Details                                                                                                                                    |
| ---- | ------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------ |
| 6.1  | **Wallet Dashboard Page** | Show NGN balance, coin balance, demo balance. Quick actions: deposit, withdraw, buy coins, sell coins.                                     |
| 6.2  | **Paystack Integration**  | Deposit: Initialize transaction → redirect/popup → webhook verification → credit wallet. Withdrawal: Transfer API → verify → debit wallet. |
| 6.3  | **Nomba Integration**     | Same flow as Paystack. User selects preferred gateway.                                                                                     |
| 6.4  | **Coin Purchase Flow**    | NGN → Coins at configured rate. Deduct from wallet, credit coin balance.                                                                   |
| 6.5  | **Coin Sell Flow**        | Coins → NGN at configured rate (with possible fee). Deduct coins, credit wallet.                                                           |
| 6.6  | **Transaction History**   | Paginated list with filters (type, status, date range). Exportable as CSV.                                                                 |
| 6.7  | **Webhook Handlers**      | Secure webhook endpoints for Paystack + Nomba. Signature verification. Idempotency (prevent double credit).                                |
| 6.8  | **Withdrawal Approval**   | Admin can set auto-approve threshold. Above threshold → manual approval in admin.                                                          |
| 6.9  | **Wallet Store (Pinia)**  | `useWalletStore` — balances, transactions, deposit/withdraw actions                                                                        |
| 6.10 | **KYC Placeholder**       | Optional identity verification fields (for compliant jurisdictions). Can be toggled off.                                                   |

**Deliverable:** Full payment pipeline with two gateways, dual currency, transaction history, and admin withdrawal approval.

---

### 💬 PHASE 7: Chat & Social Features (Week 8-9)

**Goal:** Build real-time public chat, and social engagement features.

#### Tasks:

| #   | Task                       | Details                                                                                            |
| --- | -------------------------- | -------------------------------------------------------------------------------------------------- |
| 7.1 | **Chat UI Component**      | Scrollable message list, input with emoji picker, auto-scroll on new messages, load older messages |
| 7.2 | **Chat Backend**           | Store messages, broadcast via Reverb public channel, rate limiting (max 1 msg/3 seconds)           |
| 7.3 | **Chat Moderation**        | Profanity filter (configurable word list), admin/mod can delete messages, mute users (timed)       |
| 7.4 | **Chat Commands**          | `/rain 1000` (rain coins to random users), system messages for big wins                            |
| 7.5 | **Online Users Display**   | Show count + scrollable avatar list of online users. Presence channel.                             |
| 7.6 | **Win Announcements**      | Auto-broadcast when a player cashes out above configurable multiplier (e.g., > 10x)                |
| 7.7 | **Emoji & GIF Support**    | Built-in emoji picker. Optional GIF support (Giphy API or custom sticker packs).                   |
| 7.8 | **Chat Store (Pinia)**     | `useChatStore` — messages, online users, send/receive actions                                      |
| 7.9 | **Player Profile Popover** | Click username in chat/bets → see mini profile: stats, avatar, join date                           |

**Deliverable:** Real-time public chat with moderation, emoji, online users, and win announcements.

---

### 🏆 PHASE 8: Leaderboard & Statistics (Week 9-10)

**Goal:** Build comprehensive leaderboard and player statistics.

#### Tasks:

| #   | Task                                | Details                                                                                                                   |
| --- | ----------------------------------- | ------------------------------------------------------------------------------------------------------------------------- |
| 8.1 | **Leaderboard UI**                  | Tabbed: Daily, Weekly, Monthly, All-Time. Show rank, avatar, username, profit, biggest win                                |
| 8.2 | **Leaderboard Calculation**         | Scheduled job (every 5 min for daily, hourly for weekly+). Cache in Redis.                                                |
| 8.3 | **Player Statistics Page**          | Personal stats: total wagered, total won, profit/loss, win rate, best multiplier, favorite bet amount, games played chart |
| 8.4 | **Game History Page**               | Full round history with crash points. Click to see: all bets for that round, provably fair verification                   |
| 8.5 | **Provably Fair Verification Page** | Input: server seed, client seed, nonce → verify crash point. Link from game history.                                      |
| 8.6 | **Live Statistics Bar**             | Show on game page: total online, total wagered today, biggest win today                                                   |

**Deliverable:** Multi-period leaderboard, personal stats, game history with provably fair verification.

---

### 🛡️ PHASE 9: Admin Panel (Filament) (Week 10-12)

**Goal:** Build a comprehensive admin dashboard using Filament PHP with a distinct theme.

#### Tasks:

| #    | Task                         | Details                                                                                                                |
| ---- | ---------------------------- | ---------------------------------------------------------------------------------------------------------------------- |
| 9.1  | **Admin Theme**              | Custom Filament theme (different color scheme from frontend). Professional dark admin look.                            |
| 9.2  | **Dashboard Widgets**        | Revenue today/week/month, active users, total bets, house profit/loss, signups chart, deposit chart                    |
| 9.3  | **User Management**          | List/search/filter users. View profile, wallet, bets, transactions. Ban/unban. Adjust balance. Assign roles.           |
| 9.4  | **Game Round Management**    | View all rounds, crash points, bets per round. Tools to monitor game health.                                           |
| 9.5  | **Transaction Management**   | View all transactions. Filter by type/status/gateway. Approve/reject pending withdrawals.                              |
| 9.6  | **Chat Moderation**          | View flagged messages. Delete messages. Ban users from chat. Manage word filter list.                                  |
| 9.7  | **Advertisement Management** | CRUD ads. Upload images, set placement, schedule, track impressions/clicks.                                            |
| 9.8  | **Game Settings**            | Configure: min/max bet, house edge, betting duration, flying object, background, sound.                                |
| 9.9  | **Payment Gateway Config**   | Manage Paystack + Nomba API keys, toggle gateways, set deposit/withdrawal limits, fees.                                |
| 9.10 | **Flying Object Manager**    | Upload sprite images/animations. Preview in canvas mockup. Set default. Allow users to see selected one.               |
| 9.11 | **Site Settings**            | Site name, logo, favicon, meta tags, maintenance mode, registration toggle, guest play toggle, coin exchange rates.    |
| 9.12 | **Notification System**      | Send announcements to all users (banner, push, email).                                                                 |
| 9.13 | **Reports & Analytics**      | Profit/loss reports, player activity, game health (average crash point distribution), payment reports. Export CSV/PDF. |
| 9.14 | **Audit Log**                | Track admin actions: setting changes, balance adjustments, bans, etc.                                                  |

**Deliverable:** Full Filament admin panel with user management, game control, payments, ads, and analytics.

---

### 🔒 PHASE 10: Security, Anti-Cheat & Rate Limiting (Week 12-13)

**Goal:** Harden the application against abuse, cheating, and attacks.

#### Tasks:

| #     | Task                           | Details                                                                                                   |
| ----- | ------------------------------ | --------------------------------------------------------------------------------------------------------- |
| 10.1  | **Rate Limiting**              | API rate limiting per endpoint. Bet placement: max 5/second. Chat: 1/3 seconds. Login: 5 attempts/minute. |
| 10.2  | **Bet Timing Validation**      | Server-side strict window: bets only accepted during BETTING phase. Reject late bets.                     |
| 10.3  | **Cashout Timing Validation**  | Server-side validation: cashout only during RUNNING phase. Compare server timestamp, not client's.        |
| 10.4  | **Multi-Account Detection**    | Track IP + fingerprint. Flag accounts sharing IPs. Admin review.                                          |
| 10.5  | **Balance Integrity**          | Use database transactions with row-level locks for all balance changes. Double-entry bookkeeping pattern. |
| 10.6  | **API Authentication**         | Sanctum tokens for API. CSRF protection for web. Signed URLs for webhooks.                                |
| 10.7  | **Input Sanitization**         | XSS protection on chat messages. SQL injection prevention (Eloquent). CSRF tokens on all forms.           |
| 10.8  | **WebSocket Auth**             | Private/presence channels require authentication. Guests limited to public game channel.                  |
| 10.9  | **Suspicious Activity Alerts** | Auto-flag: unusual win streaks, rapid deposits/withdrawals, bot-like behavior. Admin notification.        |
| 10.10 | **DDoS Mitigation**            | Cloudflare recommendation in docs. Application-level throttling on heavy endpoints.                       |
| 10.11 | **Encryption**                 | All sensitive data encrypted at rest (wallet balances, seeds). HTTPS enforced.                            |
| 10.12 | **Responsible Gaming**         | Self-exclusion option. Deposit limits per day/week/month. Cool-down timer option.                         |

**Deliverable:** Production-grade security with anti-cheat, rate limiting, balance integrity, and responsible gaming features.

---

### 🧪 PHASE 11: Testing & QA (Week 13-14)

**Goal:** Comprehensive testing to ensure reliability and Codecanyon quality.

#### Tasks:

| #     | Task                      | Details                                                                               |
| ----- | ------------------------- | ------------------------------------------------------------------------------------- |
| 11.1  | **Unit Tests**            | Provably fair engine, crash point calculation, payout calculation, balance operations |
| 11.2  | **Feature Tests**         | Auth flows, bet placement, cashout, wallet operations, chat, API endpoints            |
| 11.3  | **WebSocket Tests**       | Game state broadcast, chat delivery, presence updates                                 |
| 11.4  | **Browser Tests (Dusk)**  | End-to-end: register → deposit → play → cashout → withdraw                            |
| 11.5  | **Load Testing**          | Simulate 100+ concurrent players. Identify bottlenecks. Optimize queries/cache.       |
| 11.6  | **Mobile Testing**        | Test on real devices: iOS Safari, Android Chrome. Touch targets, canvas rendering.    |
| 11.7  | **Cross-Browser Testing** | Chrome, Firefox, Safari, Edge. Canvas rendering, WebSocket connection.                |
| 11.8  | **Security Audit**        | OWASP top 10 check. Pen testing basic. SQL injection, XSS, CSRF manual tests.         |
| 11.9  | **Payment Testing**       | Paystack/Nomba test mode. Full deposit/withdrawal cycle. Webhook reliability.         |
| 11.10 | **Edge Cases**            | Disconnection during bet/cashout, server restart mid-round, race conditions.          |

**Deliverable:** Test suite with 80%+ coverage. QA report. All critical/major bugs resolved.

---

### 📦 PHASE 12: Polish, Documentation & Codecanyon Submission (Week 14-16)

**Goal:** Final polish, documentation, and prepare for Codecanyon submission.

#### Tasks:

| #     | Task                         | Details                                                                                                                 |
| ----- | ---------------------------- | ----------------------------------------------------------------------------------------------------------------------- |
| 12.1  | **Performance Optimization** | Lazy load Vue components. Image optimization. Query optimization (N+1). Redis caching strategy. CDN setup docs.         |
| 12.2  | **SEO & Meta Tags**          | OpenGraph, Twitter Card, structured data. Configurable from admin.                                                      |
| 12.3  | **PWA Support (Optional)**   | Service worker, manifest.json, install prompt. Offline fallback page.                                                   |
| 12.4  | **Installer Script**         | Web-based installer: check requirements, configure `.env`, run migrations, create admin, set up cron.                   |
| 12.5  | **Documentation Site**       | Full documentation: installation, configuration, customization, API reference, troubleshooting. Markdown → static HTML. |
| 12.6  | **Demo Data Seeder**         | Rich demo data: 50 users, 500 rounds, 5000 bets, chat history. For buyer preview.                                       |
| 12.7  | **Screenshots & Preview**    | High-quality screenshots: game, mobile, admin, dark mode, light mode.                                                   |
| 12.8  | **Preview Video**            | 60-90 second video showing gameplay, features, admin. Professional voiceover or music.                                  |
| 12.9  | **Codecanyon Description**   | Feature list, requirements, changelog, support policy. Compelling copy.                                                 |
| 12.10 | **License System**           | Envato purchase code verification. Basic license check on install.                                                      |
| 12.11 | **CHANGELOG.md**             | Document all versions. Start with v1.0.0.                                                                               |
| 12.12 | **Final QA Pass**            | Full regression test. Fresh install test. Documentation review.                                                         |
| 12.13 | **Submit to Codecanyon**     | Upload package. Submit for review.                                                                                      |

**Deliverable:** Polished, documented, Codecanyon-ready package.

---

## 6. Feature Breakdown

### Player-Facing Features

| Feature                    | Description                                         | Priority    |
| -------------------------- | --------------------------------------------------- | ----------- |
| Real-time Crash Game       | Canvas-animated multiplier curve with flying object | 🔴 Critical |
| Place Bet                  | Set amount, place before round starts               | 🔴 Critical |
| Cash Out                   | Manual button press during round                    | 🔴 Critical |
| Auto Cash Out              | Set target multiplier for automatic cashout         | 🔴 Critical |
| Auto Bet                   | Configure automatic betting across rounds           | 🟡 High     |
| Two Simultaneous Bets      | Place 2 independent bets per round                  | 🟡 High     |
| Guest/Demo Play            | Play without registration using demo coins          | 🔴 Critical |
| Public Chat                | Real-time chat with all players                     | 🟡 High     |
| Leaderboard                | Daily/weekly/monthly/all-time rankings              | 🟡 High     |
| Game History               | Past rounds with crash points                       | 🟡 High     |
| Provably Fair Verification | Verify any round's fairness                         | 🔴 Critical |
| Wallet (Deposit/Withdraw)  | Paystack + Nomba integration                        | 🔴 Critical |
| Coin Purchase/Sell         | Buy/sell virtual coins with NGN                     | 🔴 Critical |
| User Profile               | Avatar, stats, settings                             | 🟢 Medium   |
| Dark/Light Mode            | Theme toggle with persistence                       | 🟡 High     |
| Sound Effects              | Togglable game sounds                               | 🟢 Medium   |
| Online Users               | Live count + list                                   | 🟡 High     |
| Notifications              | Win alerts, deposit confirmations                   | 🟢 Medium   |
| Social Login               | Google + GitHub                                     | 🟢 Medium   |
| Mobile Responsive          | Full mobile experience                              | 🔴 Critical |
| Ads Display                | Admin-placed advertisements                         | 🟢 Medium   |

### Admin Features

| Feature               | Description                      | Priority    |
| --------------------- | -------------------------------- | ----------- |
| Dashboard Analytics   | Revenue, users, bets, charts     | 🔴 Critical |
| User Management       | CRUD, ban, adjust balance, roles | 🔴 Critical |
| Game Settings         | Min/max bet, house edge, timing  | 🔴 Critical |
| Payment Config        | Gateway keys, limits, fees       | 🔴 Critical |
| Withdrawal Approval   | Review & approve/reject          | 🔴 Critical |
| Flying Object Manager | Upload/replace game sprite       | 🟡 High     |
| Ad Management         | CRUD advertisements              | 🟡 High     |
| Chat Moderation       | Delete messages, mute/ban        | 🟡 High     |
| Site Settings         | Branding, meta, toggles          | 🟡 High     |
| Reports               | Profit/loss, player activity     | 🟡 High     |
| Game Round Monitor    | View round history & health      | 🟢 Medium   |
| Audit Log             | Track admin actions              | 🟢 Medium   |
| Announcements         | Broadcast messages               | 🟢 Medium   |

---

## 7. Game Engine Design

### Server-Side Game Loop

```
┌─────────────────────────────────────────────────────┐
│                  GAME ROUND LIFECYCLE                 │
│                                                       │
│  ┌──────────┐    ┌──────────┐    ┌──────────┐        │
│  │ WAITING  │───▶│ BETTING  │───▶│ RUNNING  │──┐     │
│  │ (3 sec)  │    │ (7-10sec)│    │ (varies) │  │     │
│  └──────────┘    └──────────┘    └──────────┘  │     │
│       ▲                                         │     │
│       │          ┌──────────┐                   │     │
│       └──────────│ CRASHED  │◀──────────────────┘     │
│                  │ (3 sec)  │                          │
│                  └──────────┘                          │
└─────────────────────────────────────────────────────┘
```

### Crash Point Algorithm (Provably Fair)

```php
// Server-side crash point calculation
function calculateCrashPoint(string $serverSeed, string $clientSeed, int $nonce): float
{
    $hash = hash_hmac('sha256', "{$clientSeed}-{$nonce}", $serverSeed);

    // Take first 8 hex characters (32 bits)
    $hex = substr($hash, 0, 8);
    $int = hexdec($hex);

    // House edge: 3%
    $houseEdge = 0.03;

    // 1 in 33 chance of instant crash (1.00x)
    if ($int % 33 === 0) {
        return 1.00;
    }

    // Calculate crash point with house edge
    $e = 2 ** 32; // 4294967296
    $crashPoint = (1 - $houseEdge) * $e / ($e - $int);

    return max(1.00, floor($crashPoint * 100) / 100);
}
```

### Client-Side Rendering

```javascript
// Simplified game renderer concept
class CrashGameRenderer {
    constructor(canvas) {
        this.canvas = canvas;
        this.ctx = canvas.getContext("2d");
        this.multiplier = 1.0;
        this.startTime = null;
        this.flyingObject = new Image(); // Admin-configurable sprite
        this.curvePoints = [];
    }

    start(serverStartTime) {
        this.startTime = serverStartTime;
        this.animate();
    }

    animate() {
        if (this.status !== "running") return;

        const elapsed = Date.now() - this.startTime;

        // Interpolate between server ticks for smooth 60fps
        this.multiplier = this.interpolateMultiplier(elapsed);

        // Clear & draw
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        this.drawBackground();
        this.drawGrid();
        this.drawCurve();
        this.drawFlyingObject();
        this.drawMultiplierText();

        requestAnimationFrame(() => this.animate());
    }

    drawCurve() {
        // Exponential curve from bottom-left
        this.ctx.beginPath();
        this.ctx.strokeStyle = "#22c55e"; // Green
        this.ctx.lineWidth = 3;
        // ... plot curve points based on multiplier
    }

    drawFlyingObject() {
        // Position sprite at the tip of the curve
        const pos = this.getCurveEndpoint();
        const angle = this.getCurveAngle();
        // Rotate and draw sprite
        this.ctx.save();
        this.ctx.translate(pos.x, pos.y);
        this.ctx.rotate(angle);
        this.ctx.drawImage(this.flyingObject, -25, -25, 50, 50);
        this.ctx.restore();
    }
}
```

---

## 8. WebSocket Events Map

### Channels

| Channel             | Type     | Purpose                                              |
| ------------------- | -------- | ---------------------------------------------------- |
| `game`              | Public   | Game state, round updates, multiplier ticks          |
| `chat`              | Public   | Chat messages                                        |
| `presence-online`   | Presence | Online users tracking                                |
| `private-user.{id}` | Private  | Personal notifications, bet results, balance updates |

### Events

| Event              | Channel           | Direction     | Payload                                                        |
| ------------------ | ----------------- | ------------- | -------------------------------------------------------------- |
| `GameRoundStarted` | game              | Server→Client | `{ round_id, round_hash, status: 'betting', betting_ends_at }` |
| `GameRunning`      | game              | Server→Client | `{ round_id, status: 'running', started_at }`                  |
| `MultiplierTick`   | game              | Server→Client | `{ round_id, multiplier, elapsed_ms }` (10-20Hz)               |
| `GameCrashed`      | game              | Server→Client | `{ round_id, crash_point, server_seed }`                       |
| `BetPlaced`        | game              | Server→Client | `{ user_id, username, avatar, amount, round_id }`              |
| `PlayerCashedOut`  | game              | Server→Client | `{ user_id, username, multiplier, payout }`                    |
| `ChatMessage`      | chat              | Server→Client | `{ user_id, username, avatar, message, type, created_at }`     |
| `ChatDeleted`      | chat              | Server→Client | `{ message_id, deleted_by }`                                   |
| `UserJoined`       | presence-online   | Server→Client | `{ user_id, username, avatar }`                                |
| `UserLeft`         | presence-online   | Server→Client | `{ user_id }`                                                  |
| `BalanceUpdated`   | private-user.{id} | Server→Client | `{ wallet_balance, coin_balance }`                             |
| `BetResult`        | private-user.{id} | Server→Client | `{ bet_id, status, payout, multiplier }`                       |
| `DepositConfirmed` | private-user.{id} | Server→Client | `{ transaction_id, amount, new_balance }`                      |
| `Notification`     | private-user.{id} | Server→Client | `{ title, message, type }`                                     |

---

## 9. API Endpoints

### Authentication

```
POST   /api/auth/register          Register new user
POST   /api/auth/login             Login
POST   /api/auth/logout            Logout
POST   /api/auth/guest             Create guest session
POST   /api/auth/social/{provider} Social login redirect
GET    /api/auth/social/{provider}/callback
POST   /api/auth/forgot-password   Request password reset
POST   /api/auth/reset-password    Reset password
POST   /api/auth/verify-email      Verify email
GET    /api/auth/user              Get current user
```

### Game

```
GET    /api/game/current           Get current round info
GET    /api/game/history           Get recent rounds (paginated)
GET    /api/game/round/{id}        Get round details + bets
POST   /api/game/bet               Place a bet
POST   /api/game/cashout           Cash out active bet
POST   /api/game/auto-bet          Save auto-bet config
GET    /api/game/my-bets           Get user's bet history
```

### Wallet

```
GET    /api/wallet/balance         Get all balances
POST   /api/wallet/deposit         Initialize deposit
POST   /api/wallet/withdraw        Request withdrawal
POST   /api/wallet/buy-coins       Purchase coins with NGN
POST   /api/wallet/sell-coins      Sell coins for NGN
GET    /api/wallet/transactions    Get transaction history
```

### Chat

```
GET    /api/chat/messages          Get recent messages
POST   /api/chat/send              Send message
DELETE /api/chat/messages/{id}     Delete message (mod/admin)
```

### Leaderboard

```
GET    /api/leaderboard/{period}   Get leaderboard (daily/weekly/monthly/alltime)
GET    /api/stats/me               Get personal statistics
GET    /api/stats/live              Get live stats (online, wagered today)
```

### Provably Fair

```
GET    /api/fair/seeds             Get current seed pair
POST   /api/fair/client-seed       Update client seed
POST   /api/fair/verify            Verify a round
GET    /api/fair/history           Get seed history
```

### User

```
GET    /api/user/profile           Get profile
PUT    /api/user/profile           Update profile
POST   /api/user/avatar            Upload avatar
PUT    /api/user/settings          Update settings (theme, sound)
GET    /api/user/notifications     Get notifications
POST   /api/user/notifications/read Mark as read
```

### Webhooks

```
POST   /webhooks/paystack          Paystack webhook
POST   /webhooks/nomba             Nomba webhook
```

---

## 10. Security & Anti-Cheat

### Rate Limiting Rules

| Endpoint         | Limit | Window     |
| ---------------- | ----- | ---------- |
| Login attempts   | 5     | 1 minute   |
| Registration     | 3     | 10 minutes |
| Place bet        | 5     | 1 second   |
| Cash out         | 3     | 1 second   |
| Chat message     | 1     | 3 seconds  |
| Deposit init     | 5     | 1 minute   |
| Withdraw request | 3     | 10 minutes |
| API general      | 60    | 1 minute   |

### Anti-Cheat Measures

1. **Server-Authoritative Game State** — All game logic runs on the server. Client only renders.
2. **Bet Window Enforcement** — Bets only accepted during BETTING phase (server timestamp).
3. **Cashout Timing Server-Side** — Cashout multiplier is determined by server clock, not client.
4. **Mutex Locks on Balance Operations** — Prevent race conditions on concurrent requests.
5. **Double-Entry Ledger** — Every balance change has a corresponding debit and credit entry.
6. **IP/Fingerprint Tracking** — Detect multi-accounting.
7. **Behavioral Analysis** — Flag bot-like patterns (exact same bet, perfect timing, no variation).
8. **WebSocket Connection Limits** — Max 3 connections per user.
9. **Encrypted Seeds** — Server seeds encrypted at rest, only revealed after round/seed rotation.
10. **Request Signing** — Critical endpoints validate request integrity.

---

## 11. Codecanyon Submission Checklist

### Mandatory Requirements

- [ ] Clean, well-commented, PSR-12 compliant code
- [ ] No hardcoded API keys or credentials
- [ ] `.env.example` with all required variables documented
- [ ] Web-based installer or clear installation guide
- [ ] Responsive design (mobile + tablet + desktop)
- [ ] Cross-browser compatibility (Chrome, Firefox, Safari, Edge)
- [ ] No console errors in production build
- [ ] Licensed dependencies only (MIT, Apache, BSD)
- [ ] Original design (no templates/themes that require separate licenses)
- [ ] Documentation (installation, configuration, usage, customization)
- [ ] Demo available (live demo URL)

### Quality Standards (for Approval)

- [ ] Professional, modern UI design
- [ ] Smooth animations (60fps canvas)
- [ ] Fast load times (< 3 second initial load)
- [ ] Proper error handling and user feedback
- [ ] Form validation (client + server)
- [ ] Secure authentication and authorization
- [ ] Database migrations (no raw SQL files)
- [ ] Seeders for demo data
- [ ] Configurable settings (no hardcoded values)
- [ ] Clean Git history (no secrets in history)

### Submission Package

```
bet4gain-v1.0.0.zip
├── source/                    # Complete source code
│   ├── app/
│   ├── config/
│   ├── database/
│   ├── public/
│   ├── resources/
│   ├── routes/
│   ├── ...
│   ├── .env.example
│   ├── composer.json
│   └── package.json
├── documentation/             # HTML documentation
│   ├── index.html
│   ├── installation.html
│   ├── configuration.html
│   ├── customization.html
│   └── assets/
├── screenshots/               # Preview images
│   ├── game-desktop-dark.png
│   ├── game-desktop-light.png
│   ├── game-mobile.png
│   ├── admin-dashboard.png
│   └── ...
└── README.txt                 # Quick start guide
```

---

## 12. Folder Structure

```
bet4gain/
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       └── RunGameLoop.php           # Artisan command for game loop
│   ├── Enums/
│   │   ├── BetStatus.php
│   │   ├── GameRoundStatus.php
│   │   ├── TransactionType.php
│   │   ├── TransactionStatus.php
│   │   └── UserRole.php
│   ├── Events/
│   │   ├── GameRoundStarted.php
│   │   ├── GameRunning.php
│   │   ├── MultiplierTick.php
│   │   ├── GameCrashed.php
│   │   ├── BetPlaced.php
│   │   ├── PlayerCashedOut.php
│   │   ├── ChatMessageSent.php
│   │   └── BalanceUpdated.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   ├── AuthController.php
│   │   │   │   ├── GameController.php
│   │   │   │   ├── BetController.php
│   │   │   │   ├── WalletController.php
│   │   │   │   ├── ChatController.php
│   │   │   │   ├── LeaderboardController.php
│   │   │   │   ├── UserController.php
│   │   │   │   ├── ProvablyFairController.php
│   │   │   │   └── WebhookController.php
│   │   │   └── PageController.php        # Blade page rendering
│   │   ├── Middleware/
│   │   │   ├── GuestPlayerMiddleware.php
│   │   │   ├── ThrottleBets.php
│   │   │   └── VerifyWebhookSignature.php
│   │   └── Requests/
│   │       ├── PlaceBetRequest.php
│   │       ├── CashoutRequest.php
│   │       ├── DepositRequest.php
│   │       ├── WithdrawRequest.php
│   │       └── ...
│   ├── Jobs/
│   │   ├── ProcessGameRound.php
│   │   ├── ProcessPayout.php
│   │   ├── ProcessDeposit.php
│   │   ├── ProcessWithdrawal.php
│   │   ├── CalculateLeaderboard.php
│   │   └── CleanupGuestAccounts.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Wallet.php
│   │   ├── CoinBalance.php
│   │   ├── Transaction.php
│   │   ├── GameRound.php
│   │   ├── Bet.php
│   │   ├── ProvablyFairSeed.php
│   │   ├── ChatMessage.php
│   │   ├── LeaderboardEntry.php
│   │   ├── Advertisement.php
│   │   ├── AutoBetConfig.php
│   │   ├── SiteSetting.php
│   │   └── Notification.php
│   ├── Services/
│   │   ├── GameEngine/
│   │   │   ├── GameService.php           # Round lifecycle management
│   │   │   ├── CrashPointCalculator.php  # Provably fair crash calculation
│   │   │   ├── BetService.php            # Bet placement & cashout
│   │   │   └── AutoBetService.php        # Auto-bet processing
│   │   ├── Payment/
│   │   │   ├── PaymentServiceInterface.php
│   │   │   ├── PaystackService.php
│   │   │   └── NombaService.php
│   │   ├── WalletService.php             # Balance management with locks
│   │   ├── ChatService.php
│   │   ├── LeaderboardService.php
│   │   └── ProvablyFairService.php
│   ├── Filament/                         # Admin panel
│   │   ├── Pages/
│   │   │   └── Dashboard.php
│   │   ├── Resources/
│   │   │   ├── UserResource.php
│   │   │   ├── GameRoundResource.php
│   │   │   ├── TransactionResource.php
│   │   │   ├── AdvertisementResource.php
│   │   │   ├── ChatMessageResource.php
│   │   │   └── SiteSettingResource.php
│   │   └── Widgets/
│   │       ├── RevenueChart.php
│   │       ├── PlayersChart.php
│   │       ├── StatsOverview.php
│   │       └── RecentBetsTable.php
│   └── Traits/
│       ├── HasWallet.php
│       └── HasCoinBalance.php
├── config/
│   ├── game.php                          # Game configuration
│   ├── payment.php                       # Payment gateway config
│   └── ...
├── database/
│   ├── migrations/
│   │   ├── create_wallets_table.php
│   │   ├── create_coin_balances_table.php
│   │   ├── create_transactions_table.php
│   │   ├── create_game_rounds_table.php
│   │   ├── create_bets_table.php
│   │   ├── create_provably_fair_seeds_table.php
│   │   ├── create_chat_messages_table.php
│   │   ├── create_leaderboard_entries_table.php
│   │   ├── create_advertisements_table.php
│   │   ├── create_auto_bet_configs_table.php
│   │   ├── create_site_settings_table.php
│   │   └── create_notifications_table.php
│   ├── factories/
│   │   └── ... (all model factories)
│   └── seeders/
│       ├── DatabaseSeeder.php
│       ├── AdminSeeder.php
│       ├── SiteSettingsSeeder.php
│       └── DemoDataSeeder.php
├── resources/
│   ├── css/
│   │   └── app.css                       # Tailwind imports + custom styles
│   ├── js/
│   │   ├── app.js                        # Vue app bootstrap
│   │   ├── echo.js                       # Laravel Echo config
│   │   ├── Components/
│   │   │   ├── Base/                     # Reusable UI components
│   │   │   │   ├── BaseButton.vue
│   │   │   │   ├── BaseInput.vue
│   │   │   │   ├── BaseModal.vue
│   │   │   │   ├── BaseCard.vue
│   │   │   │   ├── BaseToast.vue
│   │   │   │   ├── BaseDropdown.vue
│   │   │   │   ├── BaseTabs.vue
│   │   │   │   └── BaseAvatar.vue
│   │   │   ├── Game/
│   │   │   │   ├── GameCanvas.vue        # Main canvas game component
│   │   │   │   ├── BetPanel.vue          # Bet placement UI
│   │   │   │   ├── AutoBetPanel.vue      # Auto-bet configuration
│   │   │   │   ├── LiveBets.vue          # Current round bets list
│   │   │   │   ├── GameHistory.vue       # Past crash points rail
│   │   │   │   ├── MultiplierDisplay.vue # Big multiplier overlay
│   │   │   │   └── CrashAnimation.vue    # Crash explosion effect
│   │   │   ├── Chat/
│   │   │   │   ├── ChatBox.vue           # Chat container
│   │   │   │   ├── ChatMessage.vue       # Single message
│   │   │   │   ├── ChatInput.vue         # Message input + emoji
│   │   │   │   └── OnlineUsers.vue       # Online users list
│   │   │   ├── Wallet/
│   │   │   │   ├── WalletDashboard.vue
│   │   │   │   ├── DepositModal.vue
│   │   │   │   ├── WithdrawModal.vue
│   │   │   │   ├── BuyCoinsModal.vue
│   │   │   │   └── TransactionHistory.vue
│   │   │   ├── Leaderboard/
│   │   │   │   ├── LeaderboardPanel.vue
│   │   │   │   └── LeaderboardRow.vue
│   │   │   ├── Auth/
│   │   │   │   ├── LoginForm.vue
│   │   │   │   ├── RegisterForm.vue
│   │   │   │   └── SocialLoginButtons.vue
│   │   │   ├── User/
│   │   │   │   ├── ProfilePage.vue
│   │   │   │   ├── SettingsPage.vue
│   │   │   │   └── PlayerPopover.vue
│   │   │   ├── Layout/
│   │   │   │   ├── AppHeader.vue
│   │   │   │   ├── MobileBottomNav.vue
│   │   │   │   ├── ThemeToggle.vue
│   │   │   │   ├── NotificationBell.vue
│   │   │   │   └── AdSlot.vue
│   │   │   └── Fair/
│   │   │       ├── FairVerifier.vue
│   │   │       └── SeedManager.vue
│   │   ├── Composables/
│   │   │   ├── useGameEngine.js          # Canvas rendering logic
│   │   │   ├── useWebSocket.js           # Echo/Reverb connection
│   │   │   ├── useTheme.js              # Dark/light mode
│   │   │   ├── useSound.js              # Sound effects manager
│   │   │   ├── useCountdown.js          # Betting countdown timer
│   │   │   └── useResponsive.js         # Responsive breakpoints
│   │   ├── Stores/
│   │   │   ├── gameStore.js             # Game state (Pinia)
│   │   │   ├── betStore.js              # Betting state (Pinia)
│   │   │   ├── userStore.js             # Auth/user state (Pinia)
│   │   │   ├── walletStore.js           # Wallet state (Pinia)
│   │   │   ├── chatStore.js             # Chat state (Pinia)
│   │   │   └── settingsStore.js         # App settings (Pinia)
│   │   ├── GameEngine/
│   │   │   ├── CrashRenderer.js          # Canvas rendering engine
│   │   │   ├── CurveCalculator.js        # Exponential curve math
│   │   │   ├── FlyingObject.js           # Sprite animation
│   │   │   ├── ParticleSystem.js         # Crash explosion particles
│   │   │   ├── BackgroundRenderer.js     # Starfield/grid background
│   │   │   └── SoundManager.js           # Audio playback
│   │   └── Utils/
│   │       ├── api.js                    # Axios instance + interceptors
│   │       ├── formatters.js             # Currency, number, date formatters
│   │       ├── validators.js             # Form validation helpers
│   │       └── constants.js              # Game constants
│   └── views/
│       ├── layouts/
│       │   ├── app.blade.php             # Main game layout
│       │   ├── auth.blade.php            # Auth pages layout
│       │   └── guest.blade.php           # Guest/landing layout
│       ├── game.blade.php               # Main game page (mounts Vue)
│       ├── auth/
│       │   ├── login.blade.php
│       │   ├── register.blade.php
│       │   ├── forgot-password.blade.php
│       │   └── reset-password.blade.php
│       ├── profile.blade.php
│       ├── wallet.blade.php
│       ├── leaderboard.blade.php
│       ├── fair.blade.php                # Provably fair page
│       ├── history.blade.php             # Game history page
│       └── installer/                    # Web installer views
│           ├── welcome.blade.php
│           ├── requirements.blade.php
│           ├── database.blade.php
│           └── complete.blade.php
├── routes/
│   ├── web.php
│   ├── api.php
│   ├── channels.php                      # WebSocket channel auth
│   └── console.php
├── public/
│   ├── assets/
│   │   ├── sprites/                      # Flying object sprites
│   │   │   ├── rocket.png
│   │   │   ├── plane.png
│   │   │   └── custom/
│   │   ├── sounds/                       # Sound effects
│   │   │   ├── bet-placed.mp3
│   │   │   ├── cashout.mp3
│   │   │   ├── crash.mp3
│   │   │   ├── tick.mp3
│   │   │   └── chat.mp3
│   │   └── images/                       # UI images
│   └── ads/                              # Uploaded ad images
├── storage/
├── tests/
│   ├── Unit/
│   │   ├── CrashPointCalculatorTest.php
│   │   ├── WalletServiceTest.php
│   │   └── BetServiceTest.php
│   └── Feature/
│       ├── AuthTest.php
│       ├── GameFlowTest.php
│       ├── BettingTest.php
│       ├── WalletTest.php
│       └── ChatTest.php
├── .env.example
├── composer.json
├── package.json
├── vite.config.js
├── tailwind.config.js
├── DEVELOPMENT_PLAN.md
├── README.md
├── INSTALL.md
├── CHANGELOG.md
└── LICENSE
```

---

## 13. Timeline Estimate

| Phase        | Description                 | Duration  | Cumulative |
| ------------ | --------------------------- | --------- | ---------- |
| **Phase 1**  | Foundation & Infrastructure | 1.5 weeks | Week 1.5   |
| **Phase 2**  | UI/UX Design & Layout       | 1.5 weeks | Week 3     |
| **Phase 3**  | Authentication & Users      | 1 week    | Week 4     |
| **Phase 4**  | Game Engine Core            | 2 weeks   | Week 6     |
| **Phase 5**  | Betting System              | 1.5 weeks | Week 7.5   |
| **Phase 6**  | Wallet & Payments           | 1.5 weeks | Week 9     |
| **Phase 7**  | Chat & Social               | 1 week    | Week 10    |
| **Phase 8**  | Leaderboard & Stats         | 1 week    | Week 11    |
| **Phase 9**  | Admin Panel                 | 2 weeks   | Week 13    |
| **Phase 10** | Security & Anti-Cheat       | 1 week    | Week 14    |
| **Phase 11** | Testing & QA                | 1.5 weeks | Week 15.5  |
| **Phase 12** | Polish & Submission         | 1.5 weeks | Week 17    |

**Total Estimated Duration: 16-17 weeks (~4 months)**

> **Note:** Timeline assumes a single full-time developer. Can be shortened to 10-12 weeks with a team of 2-3 developers working in parallel on frontend/backend/admin.

---

## Development Principles

1. **Server is the Authority** — Never trust the client. All game logic, balance operations, and validation happen server-side.
2. **Mobile-First** — Design for mobile first, then enhance for desktop.
3. **Performance** — Target 60fps canvas, < 100ms bet placement, < 3s page load.
4. **Security by Default** — Every endpoint is rate-limited, validated, and authenticated (except public game view).
5. **Configurability** — Everything the admin might want to change is a setting, not hardcoded.
6. **Clean Code** — PSR-12, type hints, docblocks, single-responsibility services.
7. **Test Coverage** — Critical paths (crash point calc, balance ops, bet flow) must have tests.

---

## Getting Started (Phase 1, Step 1)

```bash
# Install dependencies
composer install
npm install

# Configure environment
cp .env.example .env
php artisan key:generate

# Install Reverb
php artisan install:broadcasting

# Install Fortify
composer require laravel/fortify

# Install Socialite
composer require laravel/socialite

# Install Filament
composer require filament/filament
php artisan filament:install --panels

# Run migrations
php artisan migrate --seed

# Start development
php artisan serve
php artisan reverb:start
npm run dev
```

---

_This plan is a living document. Update it as requirements evolve and phases are completed._

**Version:** 1.0.0
**Last Updated:** March 5, 2026
**Author:** Development Team
