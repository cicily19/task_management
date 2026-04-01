# Task Management API (Laravel 11 + Vue 3)

**Database:** MySQL 8.x (see `database/migrations` and `database/task_management_mysql_dump.sql`)

REST API for tasks with priority ordering, strict status progression, delete-only-when-done, and a daily report by due date. The home page is a Vue 3 + Vite + Tailwind UI that calls the JSON API.

## Requirements

- PHP **8.2+** (Laravel 11 and PHPUnit 11 require it)
- Composer 2.x
- Node.js 18+ and npm
- MySQL 8.x

> This repository includes `composer.lock`. Run `composer install` on a machine that meets the PHP version requirement.

## Local setup

1. **Clone and install PHP dependencies**

   ```bash
   composer install
   ```

2. **Environment**

   ```bash
   copy .env.example .env
   php artisan key:generate
   ```

   Edit `.env` and set MySQL credentials, for example:

   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=task_management
   DB_USERNAME=root
   DB_PASSWORD=your_password
   ```

   Create the database in MySQL:

   ```sql
   CREATE DATABASE task_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

3. **Migrations and seeders**

   ```bash
   php artisan migrate
   php artisan db:seed
   ```

4. **Frontend**

   ```bash
   npm install
   npm run build
   ```

   For development with hot reload:

   ```bash
   npm run dev
   ```

5. **Run the app**

   ```bash
   php artisan serve
   ```

   Open `http://127.0.0.1:8000` for the Vue UI. API base path: `http://127.0.0.1:8000/api`.

## Tests

PHPUnit is configured to use an in-memory SQLite database (`phpunit.xml`). On PHP 8.2+:

```bash
php artisan test
```

or:

```bash
./vendor/bin/phpunit
```

## API examples

Base URL: `/api` (e.g. `http://127.0.0.1:8000/api`).

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/tasks` | Create task (body: `title`, `due_date`, `priority`) |
| `GET` | `/tasks` | List tasks; optional `?status=pending\|in_progress\|done` |
| `PATCH` | `/tasks/{id}/status` | Body: `status` — only `pending → in_progress → done` |
| `DELETE` | `/tasks/{id}` | Only when status is `done` (otherwise 403) |
| `GET` | `/tasks/report?date=YYYY-MM-DD` | Counts per priority and status for tasks **due on** that date |

**Create task**

```bash
curl -s -X POST http://127.0.0.1:8000/api/tasks \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{\"title\":\"Write tests\",\"due_date\":\"2026-04-15\",\"priority\":\"high\"}"
```

**List tasks (optional filter)**

```bash
curl -s "http://127.0.0.1:8000/api/tasks?status=pending" -H "Accept: application/json"
```

**Advance status**

```bash
curl -s -X PATCH http://127.0.0.1:8000/api/tasks/1/status \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{\"status\":\"in_progress\"}"
```

**Delete (only if done)**

```bash
curl -s -i -X DELETE http://127.0.0.1:8000/api/tasks/1 -H "Accept: application/json"
```

**Daily report**

```bash
curl -s "http://127.0.0.1:8000/api/tasks/report?date=2026-04-01" -H "Accept: application/json"
```

## SQL dump (MySQL)

File: `database/task_management_mysql_dump.sql`

Import (adjust path and credentials):

```bash
mysql -u root -p task_management < database/task_management_mysql_dump.sql
```

Edit dates in the dump if you need them to match your environment. Prefer `php artisan migrate --seed` for a consistent schema and demo data.

## Deploying online (MySQL)

Typical flow for **Render**, **Railway**, **Fly.io**, or similar:

1. Provision a **MySQL** instance and note host, port, database name, user, and password.
2. Set environment variables on the app service: `APP_KEY` (generate with `php artisan key:generate --show`), `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, and all `DB_*` variables to point at MySQL.
3. Build: `composer install --no-dev --optimize-autoloader` and `npm ci && npm run build`.
4. Release: `php artisan migrate --force` (and optionally `php artisan db:seed --force`).
5. Web server document root must be `public/`. Use the platform’s PHP buildpack or a Docker image that runs `php-fpm` + nginx/apache.

A public URL is **not included** with this submission; after you deploy, use your platform’s generated HTTPS URL for reviewers.

## Project structure (high level)

- `app/Http/Controllers/Api/TaskController.php` — API actions and business rules
- `app/Http/Requests/` — validation for create and status update
- `app/Models/Task.php` — Eloquent model with enum casts
- `app/Enums/` — `TaskPriority`, `TaskStatus`
- `database/migrations/` — `tasks` table + unique `(title, due_date)`
- `resources/js/App.vue` — Vue UI
- `routes/api.php` — API routes (prefixed with `/api` by Laravel)
