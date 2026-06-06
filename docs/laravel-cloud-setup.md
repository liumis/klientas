# Laravel Cloud setup (Pakaitinis)

Complete setup for production: deploy hooks, queue workers, and verification.

---

## 1) Required environment variables

In **Environment → Custom environment variables**:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://pakaitinis-main-ymcrrk.laravel.cloud

QUEUE_CONNECTION=database
SESSION_DRIVER=database
CACHE_STORE=database

DB_CONNECTION=mysql
# DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD are injected by Laravel Cloud

MARKSIGN_TOKEN=your-token
MAIL_MAILER=...
MAIL_FROM_ADDRESS=...
MAIL_FROM_NAME=Sit&Go

DB_IMPORT_ENABLED=false
```

Use **PHP 8.4** runtime (not 8.5) for Composer dependency compatibility.

---

## 2) Deploy commands (run on every deployment)

**Environment → Deploy commands** — paste exactly:

```bash
composer run-script --no-dev deploy
```

This runs:

- `php artisan migrate --force`
- `php artisan filament:optimize`

### Do NOT add to Deploy commands

- `php artisan queue:work` (use Background process instead)
- `php artisan queue:restart` (Cloud restarts workers automatically)
- `php artisan optimize:clear`

---

## 3) Queue worker (persistent background process)

`queue:work` must run continuously, not as a one-off Command.

### Steps

1. Open **Infrastructure** → click **App cluster** → **Settings**
2. **General** → **Background processes** → **Add process**
3. Choose **Queue worker** (or custom command):

```bash
php artisan queue:work database --queue=default --sleep=3 --tries=3 --timeout=120 --max-time=3600
```

4. Set **process count** to `1` (use `2` if queue gets busy)
5. Save and **Deploy**

Cloud will restart workers after each deployment automatically.

---

## 4) First-time / after deploy checklist

1. Deploy latest `main` branch
2. Confirm deploy commands finished successfully (Deployments log)
3. Confirm background process status is **Running**
4. In **Commands**, verify migrations:

```bash
php artisan migrate:status
```

5. Verify queue tables exist:

```bash
php artisan tinker --execute="dump(Schema::hasTable('jobs'), Schema::hasTable('failed_jobs'));"
```

---

## 5) Test that queue works

1. Submit a claim on the public form
2. In **Commands**, check pending jobs:

```bash
php artisan tinker --execute="dump(DB::table('jobs')->count());"
```

3. After a few seconds, count should go to `0` and claim status should move forward (PDF / MarkSign / email flow)

---

## 6) Failed jobs troubleshooting

List failed jobs:

```bash
php artisan queue:failed
```

Retry all:

```bash
php artisan queue:retry all
```

Flush failed list (only if you intentionally want cleanup):

```bash
php artisan queue:flush
```

---

## 7) Temporary DB import page

Only when needed:

```env
DB_IMPORT_ENABLED=true
```

Then open (while logged in):

`/secure/db`

Disable immediately after import:

```env
DB_IMPORT_ENABLED=false
```

---

## 8) Local development (optional)

Terminal 1:

```bash
php artisan serve --host=127.0.0.1 --port=8001
```

Terminal 2:

```bash
php artisan queue:work database --queue=default --sleep=3 --tries=3 --timeout=120
```

Admin panel: `http://127.0.0.1:8001/secure/login`
