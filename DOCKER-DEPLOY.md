````markdown
# 🐳 Docker Deployment Guide

## Durra Alaseel Web

---

## Prerequisites

- Docker Desktop (or Docker Engine + Compose plugin)
- Git

---

## 🚀 Quick Deploy

```bash
git clone <repo-url>
cd durra-alaseel-web
cp .env.docker.example .env
```
````

> ⚠️ Edit `.env` — set `APP_KEY`, `DB_PASSWORD`, payment keys, etc.

```bash
docker compose up -d --build
docker compose exec web mkdir -p storage/framework/sessions storage/framework/cache storage/framework/views
docker compose exec web chmod -R 775 storage bootstrap/cache
docker compose exec web php artisan migrate --force
docker compose exec web php artisan db:seed --force
docker compose exec web php artisan passport:install
docker compose exec web php artisan storage:link
```

Open **`http://localhost:8001`**

---

## 📁 Files Included

| File                         | Purpose                                                       |
| ---------------------------- | ------------------------------------------------------------- |
| `Dockerfile`                 | Multi-stage build: Node 22 → PHP 8.4 FPM                      |
| `docker-compose.yml`         | Nginx + PHP + MySQL 8.4 + Redis 7                             |
| `.docker/nginx/default.conf` | Nginx config with security headers, gzip, caching             |
| `.env.docker.example`        | All environment variables with defaults                       |
| `.dockerignore`              | Excludes node_modules, vendor, tests, logs from build context |

---

## 🏗 Architecture

```
┌─────────────┐    ┌──────────────┐    ┌─────────────┐
│   Browser    │───▶│    Nginx     │───▶│  PHP-FPM    │
│  :8001      │    │  (port 80)   │    │  :9000      │
└─────────────┘    └──────────────┘    └──────┬──────┘
                                               │
                                    ┌──────────┴──────────┐
                                    │                     │
                               ┌────▼─────┐        ┌─────▼────┐
                               │  MySQL   │        │  Redis   │
                               │  :3306   │        │  :6379   │
                               └──────────┘        └──────────┘
```

| Service   | Role                                 | Exposed to Host        |
| --------- | ------------------------------------ | ---------------------- |
| **nginx** | Reverse proxy, static files, FastCGI | ✅ `:8001`             |
| **web**   | PHP 8.4 FPM (non-root `appuser`)     | ❌                     |
| **mysql** | MySQL 8.4                            | ❌ (access via `exec`) |
| **redis** | Redis 7.4                            | ❌ (access via `exec`) |

---

## ⚙️ Environment Variables

### Required

| Variable           | Description                              |
| ------------------ | ---------------------------------------- |
| `APP_KEY`          | Generate with `php artisan key:generate` |
| `DB_PASSWORD`      | Database password for `durra_user`       |
| `DB_ROOT_PASSWORD` | MySQL root password                      |

### Optional Services

| Variable           | Description                           |
| ------------------ | ------------------------------------- |
| `CACHE_DRIVER`     | `file` (default) or `redis`           |
| `SESSION_DRIVER`   | `file` (default) or `redis`           |
| `QUEUE_CONNECTION` | `sync` (default) or `redis`           |
| `APP_PORT`         | Host port for nginx (default: `8001`) |

### External Integrations

| Variable                                                  | Service             |
| --------------------------------------------------------- | ------------------- |
| `PUSHER_APP_KEY / PUSHER_APP_SECRET / PUSHER_APP_CLUSTER` | Pusher broadcasting |
| `TWILIO_ACCOUNT_SID / TWILIO_AUTH_TOKEN`                  | Twilio SMS/OTP      |
| `MOYASAR_API_KEY`                                         | Moyasar payments    |
| `MAIL_HOST / MAIL_USERNAME / MAIL_PASSWORD`               | SMTP mail           |

---

## 🔧 Common Operations

### Restart services

```bash
docker compose restart
```

### Rebuild after code changes

```bash
docker compose up -d --build
```

### View logs

```bash
docker compose logs -f                # all services
docker compose logs -f web             # PHP only
docker compose logs -f nginx           # nginx only
docker compose logs -f mysql           # MySQL only
```

### Run migrations

```bash
docker compose exec web php artisan migrate --force
```

### Run seeders

```bash
docker compose exec web php artisan db:seed --force
```

### Clear caches

```bash
docker compose exec web php artisan cache:clear
docker compose exec web php artisan config:clear
docker compose exec web php artisan route:clear
docker compose exec web php artisan view:clear
```

### Access MySQL

```bash
docker compose exec mysql mysql -u durra_user -p durra_alaseel_db
```

### Access Redis

```bash
docker compose exec redis redis-cli
```

### Artisan tinker

```bash
docker compose exec web php artisan tinker
```

---

## 🟢 Production Mode

### 1. Enable Redis

Update `.env`:

```env
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### 2. Enable queue worker + cron scheduler

```bash
docker compose --profile queue --profile scheduler up -d
```

### 3. Switch to production env

```env
APP_ENV=production
APP_DEBUG=false
```

### 4. Cache everything

```bash
docker compose exec web php artisan config:cache
docker compose exec web php artisan route:cache
docker compose exec web php artisan view:cache
docker compose exec web php artisan event:cache
```

---

## 🔴 Tear Down

### Stop containers (keep data)

```bash
docker compose down
```

### Stop + remove volumes (destroys database)

```bash
docker compose down -v
```

### Full reset from scratch

```bash
docker compose down -v
docker compose build --no-cache
docker compose up -d
docker compose exec web mkdir -p storage/framework/sessions storage/framework/cache storage/framework/views
docker compose exec web chmod -R 775 storage bootstrap/cache
docker compose exec web php artisan migrate --force
docker compose exec web php artisan db:seed --force
docker compose exec web php artisan passport:install
docker compose exec web php artisan storage:link
```

---

## 🐛 Troubleshooting

### 500 Error

```bash
docker compose exec web cat storage/logs/laravel.log
```

### Permission denied on storage

```bash
docker compose exec web chmod -R 775 storage bootstrap/cache
```

### Missing session directory

```bash
docker compose exec web mkdir -p storage/framework/sessions storage/framework/cache storage/framework/views
```

### Database connection failed

- Verify `DB_HOST=mysql` in `.env` (not `127.0.0.1` or `db`)
- Verify `DB_USERNAME` is **NOT** `root`
- Check MySQL health: `docker compose ps`

### Redis connection refused

- Verify `REDIS_HOST=redis` in `.env` (not `127.0.0.1`)
- Verify `REDIS_PASSWORD=` is **empty** (not the string `"null"`)
- Or switch to file driver: `CACHE_DRIVER=file` + `SESSION_DRIVER=file`

### Port already in use

```env
APP_PORT=8002
```

### Stale log file (old errors persisting)

```bash
docker compose down -v
docker compose up -d
```

---

## 🔒 Security Hardening

| Measure                 | Detail                                                         |
| ----------------------- | -------------------------------------------------------------- |
| Non-root PHP            | Runs as `appuser` (UID 1000)                                   |
| No privilege escalation | `no-new-privileges:true` on all containers                     |
| Read-only nginx         | `read_only: true` with `tmpfs` for temp dirs                   |
| No build tools in image | gcc, make, autoconf removed after compilation                  |
| PHP hardening           | `expose_php=Off`, `allow_url_fopen=Off`                        |
| Redis lockdown          | `FLUSHDB`, `FLUSHALL`, `DEBUG` commands disabled               |
| MySQL hardening         | `local-infile=0`, `skip-symbolic-links`                        |
| Path blocking           | `/storage`, `/vendor`, `/artisan`, `/.env` blocked in nginx    |
| Static asset caching    | `immutable` header, 30-day expiry                              |
| Gzip compression        | CSS, JS, JSON, SVG, fonts compressed                           |
| Security headers        | `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy` |

```

```

### Quick reference

| What changed                                 | Command                                               |
| -------------------------------------------- | ----------------------------------------------------- |
| PHP files (controllers, views, routes, etc.) | `docker compose restart web`                          |
| Composer packages (`composer.json`)          | `docker compose up -d --build`                        |
| Frontend assets (CSS, JS, Blade)             | `docker compose up -d --build`                        |
| `.env` file                                  | `docker compose restart web`                          |
| `docker-compose.yml` or `Dockerfile`         | `docker compose up -d --build`                        |
| Database migrations                          | `docker compose exec web php artisan migrate --force` |
| New npm packages (`package.json`)            | `docker compose up -d --build`                        |
