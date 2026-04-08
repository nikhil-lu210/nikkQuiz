# NikkQuiz — Lightweight Quiz Platform

File-based quiz system: **PHP (OOP)**, **Tailwind CSS**, **jQuery**. No database — JSON files under `data/`. No Composer or npm build.

---

## Features

- **Student batches** — Each batch has a name, **teacher name**, and **teacher password** (bcrypt). Teachers sign in to manage that batch only.
- **Inside a batch** — Two tabs: **Participants** and **Quizzes**.
- **Participants** — **Assign participants** opens a modal; each student gets a **unique 6-digit PIN** (unique across the whole system). PINs identify them on any quiz link for **this batch only** (wrong-batch PINs are rejected).
- **Quizzes** — **Assign new quiz** opens a modal: quiz name, time limit, questions to display, and **JSON upload** in one step. Each quiz gets a **unique public link** (`take_quiz.php?q=…`).
- **Quiz status** — Active / inactive (inactive quizzes are hidden from the student catalog).
- **Timed attempts**, **randomized question subsets**, **auto-grading** with grade bands.
- **Optional** — `quizzes.php` lists all **active** quizzes across batches for students.
- **Statistics** — Students use **`my_stats.php`** (PIN) to see their scores and completion status for every quiz in their batch. Teachers get a **Statistics** tab on the batch page (matrix of all participants × quizzes) and a detailed **quiz** report on `quiz.php` (class average, per-student rows, question pool).

---

## Requirements

| Requirement | Version |
|-------------|---------|
| PHP         | 8.0+    |
| Web server  | Apache / Laragon / nginx |

---

## Run locally

Open `http://localhost/nikkQuiz/` (Laragon) or `php -S localhost:8000` from the project root.

### Site password (owner login)

Teacher pages (`index.php`, `batch.php`, `quiz.php`) and all teacher API actions require a **single site-wide password** so random visitors cannot create batches or call admin APIs. **Students are not blocked:** `take_quiz.php`, `quizzes.php`, and `my_stats.php` stay usable with quiz links and PINs only.

1. Copy `config.local.php.example` to `config.local.php`.
2. Set `site_password` to a long secret, **or** set `site_password_hash` to a bcrypt hash from `password_hash()` (see comments in the example file).
3. Open the app — you will be redirected to `login.php` until you sign in. Use **Sign out site** on the home page (or `logout_site.php`) when finished.

`config.local.php` is listed in `.gitignore` — do not commit it.

---

## Project structure

```
nikkQuiz/
├── config.php                 # Loads optional config.local.php
├── config.local.php.example   # Copy to config.local.php (site password)
├── includes/SiteAuth.php      # Owner session + page guard
├── login.php                  # Site password form
├── logout_site.php            # Clears owner session
├── api/handler.php
├── classes/
│   ├── BatchManager.php    # Batches + teacher password
│   ├── QuizManager.php     # Quizzes (per batch), slugs, attempts
│   └── Participant.php     # Roster PINs, attempts, scoring
├── data/
│   ├── batch_*.json
│   └── quiz_*.json
├── index.php               # List / create batches
├── batch.php               # Teacher sign-in + Participants & Quizzes tabs
├── quiz.php                # Results & question pool (teacher, session)
├── quizzes.php             # Student: all active quizzes
├── my_stats.php            # Student: personal stats (PIN) for the whole batch
├── take_quiz.php           # Public link + PIN + timer
├── sample_questions.json
└── README.md
```

---

## Teacher workflow

1. **Home** (`index.php`) — **Create batch**: batch name, teacher name, teacher password. You are signed in and redirected to the batch workspace.
2. **Batch** (`batch.php?id=batch_…`) — If needed, enter the **teacher password** again.
3. **Participants** tab — **Assign participants** → enter name → student gets **PIN** (and ID like `STU-xxxxx`).
4. **Quizzes** tab — **Assign new quiz** → name, time limit, questions to display, **JSON file** → quiz is created with a **copyable link**. Share that link (or point students to `quizzes.php`).
5. **Results** — Open **Results & questions** on a quiz row, or use `quiz.php?batch_id=…&id=quiz_…`.

---

## Student workflow

1. Open the **quiz link** or **quizzes.php**.
2. Enter the **6-digit PIN** from the teacher (same PIN for every quiz in that batch).
3. Timer starts after PIN verification; submit answers; see score.

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

Use **`answer`** (recommended): **0-based** index into `options` (`0` = first choice).

Or use **`correct`** as **1-based** (`1` = first choice, `2` = second, … up to the number of options). Values must match the option count: e.g. with three options, only `1`, `2`, or `3` are valid for `correct`. If `correct` is out of range (a common mistake when editing JSON), import fails until you fix it — otherwise the stored “correct” index can point to a choice that does not exist on screen.

---

## Security notes

- Teacher passwords and PIN checks are server-side; quiz links use unguessable slugs.
- Use **HTTPS** in production; protect `data/` from direct download via web server config if needed.
- **Legacy batches** without a teacher password get a default hash for password **`changeme`** on first load (change it by editing the batch file or recreate the batch).

---

## License

Provided as-is for educational and personal use.
