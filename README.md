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

4. **Deploy** – Use `.cpanel.yml` or upload the repo; ensure `client/dist/` and the PHP files are in your web directory.

See `client/README.md` for building the React app (`cd client && npm run build`).
