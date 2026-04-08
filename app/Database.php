<?php

declare(strict_types=1);

/**
 * SQLite connection + schema. Path from config.php (sqlite_path).
 */
final class Database
{
    private static ?\PDO $pdo = null;

    public static function pdo(): \PDO
    {
        if (self::$pdo === null) {
            $cfg = require dirname(__DIR__) . '/config.php';
            $path = $cfg['sqlite_path'] ?? (dirname(__DIR__) . '/data/nikkquiz.sqlite');
            $dir = dirname($path);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            self::$pdo = new \PDO('sqlite:' . $path, null, null, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);
            self::$pdo->exec('PRAGMA foreign_keys = ON');
            self::ensureSchema(self::$pdo);
        }

        return self::$pdo;
    }

    public static function resetForTests(): void
    {
        self::$pdo = null;
    }

    private static function ensureSchema(\PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS batches (
                id TEXT PRIMARY KEY,
                name TEXT NOT NULL,
                teacher_name TEXT NOT NULL,
                teacher_password TEXT NOT NULL,
                created_at TEXT NOT NULL
            )'
        );
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS participants (
                id TEXT PRIMARY KEY,
                batch_id TEXT NOT NULL REFERENCES batches(id) ON DELETE CASCADE,
                name TEXT NOT NULL,
                pin TEXT NOT NULL UNIQUE
            )'
        );
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_participants_batch ON participants(batch_id)');

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS quizzes (
                id TEXT PRIMARY KEY,
                batch_id TEXT NOT NULL REFERENCES batches(id) ON DELETE CASCADE,
                name TEXT NOT NULL,
                time_limit INTEGER NOT NULL,
                total_display_questions INTEGER NOT NULL,
                public_slug TEXT NOT NULL UNIQUE,
                status TEXT NOT NULL DEFAULT \'active\',
                created_at TEXT NOT NULL
            )'
        );
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_quizzes_batch ON quizzes(batch_id)');

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS questions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                quiz_id TEXT NOT NULL REFERENCES quizzes(id) ON DELETE CASCADE,
                pool_index INTEGER NOT NULL,
                q_ref_id INTEGER,
                question_text TEXT NOT NULL,
                options_json TEXT NOT NULL,
                answer_index INTEGER NOT NULL,
                UNIQUE(quiz_id, pool_index)
            )'
        );
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_questions_quiz ON questions(quiz_id)');

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS attempts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                quiz_id TEXT NOT NULL REFERENCES quizzes(id) ON DELETE CASCADE,
                batch_id TEXT NOT NULL,
                participant_id TEXT NOT NULL,
                participant_name TEXT NOT NULL,
                status TEXT NOT NULL,
                start_time TEXT,
                end_time TEXT,
                marks INTEGER NOT NULL DEFAULT 0,
                UNIQUE(quiz_id, batch_id, participant_id)
            )'
        );
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_attempts_quiz ON attempts(quiz_id)');

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS attempt_assigned (
                attempt_id INTEGER NOT NULL REFERENCES attempts(id) ON DELETE CASCADE,
                sort_order INTEGER NOT NULL,
                pool_index INTEGER NOT NULL,
                PRIMARY KEY (attempt_id, sort_order)
            )'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS attempt_answers (
                attempt_id INTEGER NOT NULL REFERENCES attempts(id) ON DELETE CASCADE,
                pool_index INTEGER NOT NULL,
                selected_index INTEGER NOT NULL,
                PRIMARY KEY (attempt_id, pool_index)
            )'
        );
    }
}
