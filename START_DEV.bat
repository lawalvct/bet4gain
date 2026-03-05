@echo off
echo Starting Bet4Gain Development Environment...
echo.
echo This will start:
echo 1. Vite Dev Server (for Vue.js frontend)
echo 2. Laravel Reverb (for WebSocket connections)
echo.

start "Vite Dev Server" cmd /k "npm run dev"
timeout /t 3 /nobreak >nul
start "Laravel Reverb" cmd /k "php artisan reverb:start"

echo.
echo Development servers are starting...
echo - Vite: http://localhost:5173
echo - Laravel: http://localhost (via Laragon)
echo - Reverb: ws://localhost:8080
echo.
echo Press any key to stop all servers...
pause >nul

taskkill /FI "WindowTitle eq Vite Dev Server*" /T /F
taskkill /FI "WindowTitle eq Laravel Reverb*" /T /F
