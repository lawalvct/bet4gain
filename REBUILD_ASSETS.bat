@echo off
echo ========================================
echo   Bet4Gain - Rebuild Assets
echo ========================================
echo.
echo This will rebuild all frontend assets...
echo.

echo [1/3] Cleaning old build...
if exist public\hot del public\hot
if exist public\build\assets rmdir /s /q public\build\assets

echo [2/3] Installing dependencies...
call npm install

echo [3/3] Building assets...
call npm run build

echo.
echo ========================================
echo   Build Complete!
echo ========================================
echo.
echo Your page should now work correctly.
echo Refresh your browser to see the changes.
echo.
pause
