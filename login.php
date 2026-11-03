<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

require_guest();

$error = null;
$email = '';

$lockedUntil = (int) ($_SESSION['login_locked_until'] ?? 0);
$secondsLeft = max(0, $lockedUntil - time());

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $email    = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    if ($secondsLeft > 0) {
        $error = "Too many attempts. Try again in {$secondsLeft} seconds.";
    } elseif ($email === '' || $password === '') {
        $error = 'Please enter your email and password.';
    } else {
        $stmt = db()->prepare('SELECT id, name, password_hash, last_login_at FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Verify against a dummy hash when the user doesn't exist, so response time doesn't leak it.
        $hash  = $user['password_hash'] ?? '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG';
        $valid = password_verify($password, $hash) && $user;

        if ($valid) {
            if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                    ->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
            }
            login_user($user);
            flash('success', 'Signed in as ' . $user['name'] . '.');
            redirect('dashboard.php');
        }

        $attempts = (int) ($_SESSION['login_attempts'] ?? 0) + 1;
        $_SESSION['login_attempts'] = $attempts;

        if ($attempts >= LOGIN_MAX_ATTEMPTS) {
            $_SESSION['login_locked_until'] = time() + LOGIN_LOCK_SECONDS;
            $_SESSION['login_attempts'] = 0;
            $error = 'Too many attempts. Try again in ' . LOGIN_LOCK_SECONDS . ' seconds.';
        } else {
            $left  = LOGIN_MAX_ATTEMPTS - $attempts;
            $error = "Invalid email or password. {$left} attempt" . ($left === 1 ? '' : 's') . ' left.';
        }
    }
}

$pageTitle  = 'Sign in';
$heading    = 'Sign in to ' . APP_NAME;
$subheading = '';
$authAlt    = ['New to ' . APP_NAME . '?', 'Create an account', 'register.php'];
require __DIR__ . '/includes/auth_header.php';
?>

<?php if ($error): ?>
    <div class="alert alert-error" role="alert">
        <?= icon('alert') ?><span><?= e($error) ?></span>
    </div>
<?php endif; ?>

<form method="post" action="login.php" class="form" novalidate data-loading-form>
    <?= csrf_field() ?>

    <div class="field">
        <label for="email">Email address</label>
        <div class="input-wrap">
            <input id="email" name="email" type="email" autocomplete="email"
                   value="<?= e($email) ?>" required <?= $email === '' ? 'autofocus' : '' ?>>
        </div>
    </div>

    <div class="field">
        <label for="password">Password</label>
        <div class="input-wrap">
            <input id="password" name="password" type="password" autocomplete="current-password"
                   required <?= $email !== '' ? 'autofocus' : '' ?>>
            <button type="button" class="toggle-pass" aria-label="Show password" data-toggle-password>
                <span class="when-hidden"><?= icon('eye') ?></span>
                <span class="when-shown"><?= icon('eye-off') ?></span>
            </button>
        </div>
    </div>

    <button type="submit" class="btn btn-primary btn-block">
        <span class="btn-label">Sign in</span>
        <span class="spinner" aria-hidden="true"></span>
    </button>
</form>

<?php require __DIR__ . '/includes/auth_footer.php'; ?>
