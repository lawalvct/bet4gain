# Fix Blank Page Issue

## Problem
The page is blank because the Vite development server is not running, but Laravel thinks it is (due to the `public/hot` file).

## Quick Fix

### Option 1: Start Development Server (Recommended)
1. Open a terminal in the project root
2. Run: `npm run dev`
3. Refresh your browser

### Option 2: Use the Batch File
1. Double-click `START_DEV.bat` in the project root
2. This will start both Vite and Reverb servers
3. Refresh your browser

### Option 3: Build for Production
If you don't want to run the dev server:
1. Delete `public/hot` file
2. Run: `npm run build`
3. Refresh your browser

## What's Happening?
- Laravel uses Vite to compile Vue.js and CSS
- The `@vite` directive in `layouts/app.blade.php` loads the compiled assets
- When `public/hot` exists, Laravel expects Vite dev server to be running
- If Vite isn't running, the page loads but JavaScript doesn't execute = blank page

## Development Workflow
Always run `npm run dev` when developing:
```bash
npm run dev
```

This starts Vite with hot module replacement (HMR) for instant updates.

## Production Deployment
Before deploying:
```bash
npm run build
```

This creates optimized production assets in `public/build/`.
