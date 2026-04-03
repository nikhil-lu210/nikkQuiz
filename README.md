# NikkQuiz — Lightweight Quiz Management System

A file-based Quiz Management System built with **PHP (OOP)** backend and **Tailwind CSS / jQuery** frontend. No database required — all data is stored as JSON files.

---

## ✨ Features

- **Admin Dashboard** — Create & manage multiple quizzes from a single interface
- **JSON Question Upload** — Upload question pools as `.json` files
- **Participant Management** — Add participants, auto-generate unique URLs & 6-digit PINs
- **Timed Quiz Engine** — Server-synced timer with randomized question subsets per participant
- **Auto-Grading** — Instant score calculation with grade bands & emoji feedback
- **No Database** — All data persisted to JSON files in the `/data` directory
- **Session-Based Admin Auth** — Password-protected quiz management

---

## 📋 Requirements

| Requirement | Version |
|-------------|---------|
| PHP         | 8.0+    |
| Web Server  | Apache / Nginx / Laragon |
| Browser     | Any modern browser (Chrome, Firefox, Edge, Safari) |

> **No Composer, no npm, no build step.** Everything runs out of the box.

---

## 🚀 Setup & Run Locally

### Option A: Using Laragon (Recommended on Windows)

1. **Clone or copy** the project folder into your Laragon `www` directory:
   ```
   C:\laragon\www\nikkQuiz\
   ```

2. **Start Laragon** — Apache + PHP will start automatically.

3. **Open your browser** and navigate to:
   ```
   http://localhost/nikkQuiz/
   ```

4. That's it! The app is ready to use.

### Option B: Using PHP's Built-in Server

1. Open a terminal and navigate to the project directory:
   ```bash
   cd /path/to/nikkQuiz
   ```

2. Start PHP's built-in server:
   ```bash
   php -S localhost:8000
   ```

3. Open your browser and navigate to:
   ```
   http://localhost:8000/
   ```

### Option C: Using XAMPP / WAMP / MAMP

1. Copy the `nikkQuiz` folder into your web server's document root:
   - **XAMPP**: `C:\xampp\htdocs\nikkQuiz\`
   - **WAMP**: `C:\wamp\www\nikkQuiz\`
   - **MAMP**: `/Applications/MAMP/htdocs/nikkQuiz/`

2. Start Apache from the control panel.

3. Navigate to `http://localhost/nikkQuiz/` in your browser.

---

## 📁 Project Structure

```
nikkQuiz/
├── api/
│   └── handler.php          # Central API endpoint (all AJAX routes)
├── classes/
│   ├── QuizManager.php      # Quiz CRUD operations (file I/O)
│   └── Participant.php      # Participant management, scoring, timing
├── data/                    # JSON quiz files (auto-created)
│   └── quiz_*.json          # One file per quiz
├── index.php                # Admin Dashboard
├── quiz_details.php         # Quiz management (questions, participants, results)
├── take_quiz.php            # Participant quiz-taking page
├── sample_questions.json    # Example question file for testing
├── README.md
└── .gitignore
```

---

## 📖 How to Use

### 1. Create a Quiz (Admin)

1. Go to `http://localhost/nikkQuiz/`
2. Click **"Create Quiz"**
3. Enter:
   - **Quiz Name** (e.g., "Midterm Exam")
   - **Time Limit** in minutes
   - **Questions to Display** per participant
   - **Admin Password** to protect the quiz
4. Click **"Create Quiz"**

### 2. Upload Questions

1. Click on a quiz card → enter the admin password to unlock
2. On the **Questions** tab, click **"Choose File"** and upload a JSON file
3. Use the format shown on the page, or use the included `sample_questions.json`

#### Question JSON Format

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

> **Note:** `answer` is a **0-based index** of the correct option. In the example above, `2` means "Paris".

### 3. Add Participants

1. Switch to the **Participants** tab
2. Click **"Add Participant"**
3. Enter the participant's **Name** and **ID**
4. A **6-digit PIN** and **unique quiz link** will be generated
5. Share the link and PIN with the participant

### 4. Taking a Quiz (Participant)

1. Open the unique quiz link (e.g., `http://localhost/nikkQuiz/take_quiz.php?uid=...`)
2. Enter the 6-digit PIN
3. Answer the randomized questions within the time limit
4. Submit — the score and grade are shown immediately

### 5. View Results (Admin)

1. Go to the quiz details page
2. Switch to the **Results** tab
3. View all completed participants with scores, percentages, and grades

---

## 🏆 Grading Scale

| Percentage | Grade      | Emoji |
|------------|------------|-------|
| 80–100%    | Excellent  | 🤩    |
| 60–79%     | Good       | 🙂    |
| 40–59%     | Average    | 😐    |
| 20–39%     | Poor       | ☹️    |
| 0–19%      | Very Poor  | 💀    |

---

## 🔒 Security Notes

- Admin passwords are hashed using PHP's `password_hash()` (bcrypt)
- Quiz management is protected by session-based authentication
- Participant PINs are strictly validated (6 digits, numeric only)
- The timer is synced between client (JavaScript) and server (PHP) — server-side check on submission prevents clock manipulation
- Quiz IDs and participant tokens use cryptographically secure random bytes

---

## 🛠️ Troubleshooting

| Issue | Solution |
|-------|----------|
| Blank page | Ensure PHP 8.0+ is running and check `php.ini` for errors |
| `data/` directory errors | Make sure the web server has write permissions to the `data/` folder |
| JSON upload fails | Validate your JSON file at [jsonlint.com](https://jsonlint.com) |
| Session issues | Ensure PHP sessions are enabled in `php.ini` |
| 404 on quiz link | Make sure the URL starts from the project root, not just `/take_quiz.php` |

---

## 📄 License

This project is provided as-is for educational and personal use.
