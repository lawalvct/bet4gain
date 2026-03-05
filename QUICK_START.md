# Bet4Gain - Quick Start Guide

## ✅ FIXED: Blank Page Issue

The blank page has been fixed! The issue was:
- Stale `public/hot` file was present
- Vite dev server wasn't running
- Assets weren't built

**Solution Applied:**
1. ✅ Deleted `public/hot`
2. ✅ Built production assets with `npm run build`
3. ✅ Page should now load correctly

## 🚀 Development Setup

### First Time Setup
```bash
# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Setup database
php artisan migrate --seed

# Build assets
npm run build
```

### Daily Development
Use the provided batch file for easy startup:
```bash
START_DEV.bat
```

Or manually:
```bash
# Terminal 1: Start Vite
npm run dev

# Terminal 2: Start Reverb (WebSocket)
php artisan reverb:start

# Terminal 3: Start Queue Worker (optional)
php artisan queue:work
```

## 🔧 Common Issues

### Blank Page
- **Cause**: Vite not running or assets not built
- **Fix**: Run `npm run dev` OR `npm run build`

### WebSocket Connection Failed
- **Cause**: Reverb server not running
- **Fix**: Run `php artisan reverb:start`

### Database Errors
- **Cause**: Migrations not run
- **Fix**: Run `php artisan migrate --seed`

### CSS Not Loading
- **Cause**: Tailwind not compiled
- **Fix**: Run `npm run build` or `npm run dev`

## 📁 Project Structure

```
bet4gain/
├── app/                    # Laravel backend
│   ├── Http/Controllers/  # API & Web controllers
│   ├── Models/            # Database models
│   └── Enums/             # Game enums
├── resources/
│   ├── js/                # Vue.js frontend
│   │   ├── Components/    # Vue components
│   │   ├── Composables/   # Vue composables
│   │   ├── Stores/        # Pinia stores
│   │   └── Utils/         # Utilities
│   └── views/             # Blade templates
├── routes/
│   ├── web.php            # Web routes
│   ├── api.php            # API routes
│   └── channels.php       # Broadcasting channels
└── public/
    └── build/             # Compiled assets
```

## 🎮 Game Features

- ✅ Real-time multiplayer crash game
- ✅ Live chat with online users
- ✅ Leaderboards (daily/weekly/all-time)
- ✅ Provably fair system
- ✅ Auto-bet & auto-cashout
- ✅ Guest play mode
- ✅ Wallet & transactions
- ✅ Social authentication

## 🌐 URLs

- **App**: http://localhost (via Laragon)
- **Vite Dev**: http://localhost:5173
- **Reverb**: ws://localhost:8080
- **Admin Panel**: http://localhost/admin

## 📝 Environment Variables

Key variables in `.env`:
```env
APP_URL=http://localhost
DB_DATABASE=bet4gain_db
REVERB_HOST=localhost
REVERB_PORT=8080
GAME_GUEST_PLAY_ENABLED=true
```

## 🆘 Need Help?

1. Check `FIX_BLANK_PAGE.md` for blank page issues
2. Check Laravel logs: `storage/logs/laravel.log`
3. Check browser console for JavaScript errors
4. Ensure all services are running (Laragon, Vite, Reverb)
