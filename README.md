# Time Tracker

Full-stack time tracker: React frontend + PHP API (MySQL).

## Deployment (cPanel / production)

1. **Database** – Create your MySQL database and user in cPanel. Note the database name, username, and password.

2. **Create `.env` on the server** – `.env` is **intentionally not in git** so your DB password is never committed. On the server (e.g. in `public_html/time/` next to `config.php`):
   - Copy `.env.example` to a new file named `.env`
   - Edit `.env` and set:
     - `DB_HOST=localhost`
     - `DB_NAME=` your database name  
     - `DB_USER=` your database user  
     - `DB_PASS=` your database password  
     - `ALLOWED_ORIGIN=https://yourdomain.com` (your frontend origin)

   Create `.env` in cPanel File Manager or over SSH; do **not** commit `.env` to git.

3. **Run schema** – Import `schema.sql` into your database (phpMyAdmin or MySQL).

4. **Build the React app** (before deploy):
   ```bash
   cd client && npm run build
   ```

5. **Deploy** – Use `.cpanel.yml` or upload manually:
   - Copy **all PHP files** (api.php, config.php, sse.php, etc.) and `.htaccess` to `/time/`.
   - Copy **contents of `client/dist/`** into `/time/`: `index.html` and the `assets/` folder. So you get `/time/index.html` and `/time/assets/`.
   - `.htaccess` is set so `/time/` serves `index.html` (React app) first; the PHP tracker is still at `/time/index.php` if you want it.

After deploy, **https://www.adamrmunday.com/time/** loads the React app.

See `client/README.md` for more on the client.
