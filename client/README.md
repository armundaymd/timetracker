# Time Tracker – React + Tailwind Client

React frontend for the Time Tracker, using Tailwind CSS and the existing PHP API.

## Project structure

- **`client/`** – React app source (Vite, Tailwind, pages, api).
- **`client/dist/`** – Built static files (created by `npm run build`). This folder is intended to be committed and deployed; your server can serve it (e.g. as the app root or next to `api.php`).
- **`client/node_modules/`** – Installed dependencies (ignored by git; run `npm install` after clone).

## Setup

1. **Install dependencies**
   ```bash
   cd client
   npm install
   ```

2. **Run the PHP backend**  
   Serve your existing PHP app (e.g. `index.php`, `api.php`) from a web server (Apache, PHP built-in server, or your host).

3. **Run the React app**
   - **Development (with proxy):**  
     In `vite.config.js`, set the proxy `target` to your PHP server URL (e.g. `http://localhost:8080` if PHP runs there). Then:
     ```bash
     npm run dev
     ```
     Open http://localhost:5173. On first visit you’ll see the login screen; enter the **Server URL** (e.g. `http://localhost:8080/time` or your production URL) and your credentials.
   - **Production:**  
     Set `VITE_API_URL` to your PHP base URL (e.g. `https://yoursite.com/time`), then:
     ```bash
     npm run build
     ```
     Upload the `dist/` folder to your server (e.g. into `/time/` next to `api.php`). Ensure your server serves `index.html` for client routes (e.g. React Router) and that API requests go to `api.php`.

## Features

- **Login** – Server URL + username/password; token stored in `localStorage`.
- **Tracker** – Large timer, task input, project dropdown, favorites, quick buttons, Start on its own line, FullCalendar (week/day/month), event create/edit/delete/drag/resize.
- **Settings** – Dark mode, change password, projects, quick-action buttons, export JSON.
- **Analytics** – Date range, total time, activity list, contribution heatmap (365 days), time by project.
- **Real-time** – SSE connection to sync timer when another device starts/stops.

## Tech

- React 18, React Router 6, Vite 5
- Tailwind CSS 3, dark mode via `class`
- FullCalendar React (timeGrid, dayGrid, interaction)
- Same API as the PHP app: token auth, `api.php?action=...`, `sse.php?token=...`
