<?php
declare(strict_types=1);

/* ---------- Database ---------- */

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    if (DB_DRIVER === 'mysql') {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        $pdo->exec('CREATE TABLE IF NOT EXISTS users (
            id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name          VARCHAR(80)  NOT NULL,
            email         VARCHAR(190) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            created_at    DATETIME     NOT NULL,
            last_login_at DATETIME     NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    } else {
        $dir = dirname(DB_SQLITE_PATH);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $pdo = new PDO('sqlite:' . DB_SQLITE_PATH, null, null, $options);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('CREATE TABLE IF NOT EXISTS users (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            name          TEXT NOT NULL,
            email         TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            created_at    TEXT NOT NULL,
            last_login_at TEXT NULL
        )');
    }

    return $pdo;
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

/* ---------- Output & navigation ---------- */

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $to): never
{
    header('Location: ' . $to);
    exit;
}

function current_script(): string
{
    return basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
}

/* ---------- CSRF ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['_csrf'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        flash('error', 'Your session expired. Please try again.');
        redirect(current_script());
    }
}

/* ---------- Flash messages ---------- */

function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function pull_flashes(): array
{
    $messages = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $messages;
}

function render_flashes(): string
{
    $html = '';
    foreach (pull_flashes() as $f) {
        $type = $f['type'] === 'success' ? 'success' : 'error';
        $html .= '<div class="alert alert-' . $type . '" role="alert" data-autodismiss>'
            . icon($type === 'success' ? 'check-circle' : 'alert', 18)
            . '<span>' . e($f['message']) . '</span>'
            . '<button type="button" class="alert-close" aria-label="Dismiss" data-dismiss>' . icon('x', 16) . '</button>'
            . '</div>';
    }
    return $html;
}

/* ---------- Auth ---------- */

function current_user(): ?array
{
    static $user = false;
    if ($user !== false) {
        return $user;
    }

    $id = $_SESSION['user_id'] ?? null;
    if (!$id) {
        return $user = null;
    }

    $stmt = db()->prepare('SELECT id, name, email, created_at, last_login_at FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $user = $stmt->fetch() ?: null;

    if ($user === null) {
        unset($_SESSION['user_id']);
    }
    return $user;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        flash('error', 'Please sign in to continue.');
        redirect('login.php');
    }
    return $user;
}

function require_guest(): void
{
    if (current_user()) {
        redirect('dashboard.php');
    }
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id']        = (int) $user['id'];
    $_SESSION['previous_login'] = $user['last_login_at'] ?? null;
    unset($_SESSION['login_attempts'], $_SESSION['login_locked_until']);

    db()->prepare('UPDATE users SET last_login_at = ? WHERE id = ?')
        ->execute([now(), $user['id']]);
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    session_start();
    session_regenerate_id(true);
}

/* ---------- Formatting helpers ---------- */

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $letters = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $letters .= mb_strtoupper(mb_substr($part, 0, 1));
    }
    return $letters ?: '?';
}

function first_name(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    return $parts[0] ?? $name;
}

function greeting(): string
{
    $h = (int) date('G');
    return match (true) {
        $h < 12 => 'Good morning',
        $h < 17 => 'Good afternoon',
        default => 'Good evening',
    };
}

function time_ago(?string $datetime): string
{
    if (!$datetime) {
        return '—';
    }
    $diff = time() - strtotime($datetime);
    if ($diff < 60)      return 'Just now';
    if ($diff < 3600)    return floor($diff / 60) . ' min ago';
    if ($diff < 86400)   return floor($diff / 3600) . ' hr ago';
    if ($diff < 604800)  return floor($diff / 86400) . ' days ago';
    return date('M j, Y', strtotime($datetime));
}

/** Stable avatar colour per user, from a small fixed palette. */
function avatar_color(string $seed): string
{
    $palette = ['#2453b8', '#1a7f37', '#9a5b13', '#6e40c9', '#b4232c', '#0b7285', '#57606a'];
    return $palette[crc32($seed) % count($palette)];
}
