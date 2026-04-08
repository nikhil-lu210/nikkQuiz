# NikkQuiz — Lightweight Quiz Platform

**PHP (OOP)**, **Tailwind CSS**, **jQuery**. Data is stored in **SQLite** (`data/nikkquiz.sqlite` by default). Teachers upload question pools as **JSON** when creating a quiz. No Composer or npm build.

---

## Table of contents

1. [Requirements](#requirements)
2. [Setup from scratch — local (Laragon / Windows)](#setup-from-scratch--local-laragon--windows)
3. [Setup from scratch — cPanel (shared hosting)](#setup-from-scratch--cpanel-shared-hosting)
4. [Configuration](#configuration)
5. [Storage, backup, and legacy JSON migration](#storage-backup-and-legacy-json-migration)
6. [Project structure](#project-structure)
7. [Features](#features)
8. [Teacher and student workflows](#teacher-and-student-workflows)
9. [Question JSON format](#question-json-format)
10. [Security notes](#security-notes)
11. [License](#license)

---

## Requirements

| Requirement | Notes |
|-------------|--------|
| **PHP** | **8.0+** (8.1+ recommended) |
| **Extensions** | `pdo`, `pdo_sqlite`, `sqlite3`, `json`, `session` (standard on Laragon and most hosts) |
| **Web server** | Apache (e.g. Laragon, cPanel) or nginx; PHP built-in server works for quick tests |

---

## Setup from scratch — local (Laragon / Windows)

Use this when the project lives on your PC (e.g. `C:\laragon\www\nikkQuiz`).

### 1. Put the project on the web root

- **Laragon:** Copy or clone the project into `laragon\www\nikkQuiz` (or another folder under `www`).
- The app expects to be run from that folder; URLs look like `http://localhost/nikkQuiz/` if the folder name is `nikkQuiz`.

### 2. PHP version

- In Laragon, ensure **PHP 8+** is selected for Apache (Menu → PHP → version).

### 3. Create local configuration (required for teachers)

Teacher pages **do not run** until a site password is configured. Student quiz and stats pages can load without it; set a password anyway before sharing the app (see [Configuration](#configuration)).

1. Copy **`config/config.local.php.example`** to **`config.local.php`** in the **project root** (same folder as `index.php`), **or** copy it to **`config/config.local.php`**.
2. Edit the file and set a strong **`site_password`**, or set **`site_password_hash`** (bcrypt) and remove the plain password. See comments inside the example file.
3. Never commit `config.local.php` (it is listed in `.gitignore`).

### 4. Writable `data` directory

- The app creates **`data/nikkquiz.sqlite`** automatically if the **`data/`** folder exists and is writable by PHP.
- On Windows with Laragon, this usually works without extra steps.
- If SQLite errors appear, ensure `data/` exists and the web server user can create files inside it.

### 5. Open the app

- **Apache (Laragon):** Open `http://localhost/nikkQuiz/` (adjust the path if your folder name differs).
- You should be redirected to **`login.php`** until you sign in with the **site password**.
- **Students** use **`take_quiz.php`**, **`quizzes.php`**, and **`my_stats.php`** with quiz links and PINs; those flows do not use the site password.

### 6. Optional: PHP built-in server (quick test)

From the **project root** (where `index.php` is):

```bash
php -S localhost:8000
```

Then open `http://localhost:8000/`. Use the same `config.local.php` steps as above.

---

## Setup from scratch — cPanel (shared hosting)

Use this when you deploy to typical **shared hosting** with **cPanel** and **Apache**.

### 1. Upload the files

- **Zip upload:** In **File Manager**, upload a `.zip` of the project to `public_html` (or a subfolder such as `public_html/nikkQuiz`), then **Extract**.
- **FTP/SFTP:** Upload the full project tree so that `index.php`, `bootstrap.php`, `config/`, `app/`, `api/`, `assets/`, and `data/` are present.

### 2. PHP version and extensions

1. Open **MultiPHP Manager** (or **Select PHP Version**).
2. Choose **PHP 8.0 or newer** for the domain or directory where NikkQuiz lives.
3. Confirm these extensions are enabled (most hosts enable them by default):

   - `pdo`
   - `pdo_sqlite`
   - `sqlite3`

If `pdo_sqlite` is missing, SQLite will not work—enable it or ask your host.

### 3. Configuration file on the server

1. On the server, copy **`config/config.local.php.example`** to **`config.local.php`** in the **project root** (next to `index.php`), **or** to **`config/config.local.php`**.
2. Set **`site_password`** (or **`site_password_hash`**) to a strong secret.
3. **Do not** commit this file to a public repository; keep it only on the server.

### 4. Permissions

- **`data/`** must be **writable** by the web server so SQLite can create/update `nikkquiz.sqlite`.
- Typical approach: directory **`data/`** mode **755** or **775** depending on host; the database file is created automatically.
- If you see errors about writing to the database, try **775** on `data/` or ask the host which user Apache/PHP runs as.

### 5. Protecting `data/` on Apache

The repo includes **`data/.htaccess`** so Apache **denies direct browser access** to `*.sqlite` and legacy `*.json` files. Keep that file when you deploy. If you use **nginx** only, block `/data/` in your server config instead (this file does not apply).

### 6. HTTPS

- In cPanel, enable **SSL** (Let’s Encrypt or your certificate) and force **HTTPS** so teacher login and cookies are not sent in clear text.

### 7. URL after install

- If the app is in `public_html/nikkQuiz/`, the site URL is:

  `https://yourdomain.com/nikkQuiz/`

- For a **subdomain** whose document root points **inside** the NikkQuiz folder, the URL is `https://quiz.yourdomain.com/` with no extra path segment.

---

## Configuration

| Setting | Description |
|--------|-------------|
| **`site_password`** | Plain-text site password for teacher/owner UI (simplest for local dev). |
| **`site_password_hash`** | Bcrypt hash; preferred on production if you avoid storing plain text. Generate with: `php -r "echo password_hash('your-secret', PASSWORD_DEFAULT), PHP_EOL;"` |
| **`sqlite_path`** | Optional. Absolute path to the SQLite file. Default: `{project root}/data/nikkquiz.sqlite`. |

**Merge order:** Defaults in `config/config.php` are overridden by **`config.local.php`** in the project root, then by **`config/config.local.php`** if present.

Until **`site_password`** or **`site_password_hash`** is set, teacher-facing pages show a short setup message (HTTP **503**). Student URLs such as **`take_quiz.php`**, **`quizzes.php`**, and **`my_stats.php`** can still load, but you should **always** set the site password before sharing the install so only you can create batches and use teacher APIs.

---

## Storage, backup, and legacy JSON migration

- **Runtime database:** `data/nikkquiz.sqlite` (default). Override with `sqlite_path` in `config.local.php`.
- **Backup:** Copy `nikkquiz.sqlite` (and your `config.local.php` securely) on a schedule. The file is gitignored by default.
- **Old JSON-only installs:** If you still have **`data/batch_*.json`** / **`data/quiz_*.json`** from an older version, run once (with an **empty** `batches` table in SQLite):

  ```bash
  php scripts/migrate_json_to_sqlite.php
  ```

  Run from the **project root** via SSH, or locally before uploading.

---

## Project structure

```
nikkQuiz/
├── bootstrap.php              # Autoloads app/*.php; defines NIKKQUIZ_ROOT
├── config.php                 # Loads config/config.php
├── config/
│   ├── config.php             # Defaults + merges config.local.php
│   └── config.local.php.example
├── app/                       # Database, managers, auth, stats
├── assets/css, assets/js
├── api/handler.php            # JSON API
├── data/
│   ├── .htaccess              # Deny web access to DB/JSON (Apache)
│   └── nikkquiz.sqlite        # Created at runtime (gitignored)
├── scripts/migrate_json_to_sqlite.php
├── index.php, batch.php, quiz.php, login.php, …
├── take_quiz.php, quizzes.php, my_stats.php
├── participant_detail.php, export_batch_stats.php, logout_site.php
├── sample_questions.json      # Example question pool for uploads
└── README.md
```

---

## Features

- **Student batches** — Name, teacher name, teacher password (bcrypt). Teachers unlock **their** batch with the teacher password.
- **Participants** — Unique **6-digit PIN** per student (globally unique in the database); PINs only work for quizzes in **that** batch.
- **Quizzes** — Create with JSON upload, time limit, random subset of questions, public link `take_quiz.php?q=…`.
- **Quiz status** — Active / inactive.
- **Statistics** — Students: `my_stats.php`. Teachers: batch matrix and `quiz.php` reports.

---

## Teacher and student workflows

### Teacher

1. **Home** (`index.php`) — Create batch (sign in with **site password** if prompted).
2. **Batch** (`batch.php?id=batch_…`) — Teacher password for that batch if required.
3. **Participants** — Add students; copy PINs.
4. **Quizzes** — New quiz → upload JSON → share link.
5. **Results** — From the batch or `quiz.php?batch_id=…&id=quiz_…`.

### Student

1. Open the quiz link or `quizzes.php`.
2. Enter **PIN** → timer → submit → see score.

---

## Question JSON format

```json
[
  {
    "id": 1,
    "question": "What is the capital of France?",
    "options": ["Berlin", "Madrid", "Paris", "Rome"],
    "answer": 2
  }
]
```

Use **`answer`** as a **0-based** index into `options`. Alternatively **`correct`** as **1-based** (see comments in older docs—values must match option count).

---

## Security notes

- Teacher and site passwords are checked **server-side**; quiz links use unguessable slugs.
- Use **HTTPS** in production.
- Keep **`data/`** out of direct download (`.htaccess` on Apache; nginx: deny `/data/`).
- **Legacy batches** loaded without a teacher password may get a default hash for **`changeme`** until updated in the database—change via the app or DB tools.

---

## License

Provided as-is for educational and personal use.
