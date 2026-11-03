<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

require_guest();

$errors = [];
$name   = '';
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name     = trim((string) ($_POST['name'] ?? ''));
    $email    = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $confirm  = (string) ($_POST['password_confirm'] ?? '');

    // Validation
    if (mb_strlen($name) < 2) {
        $errors['name'] = 'Please enter your full name.';
    } elseif (mb_strlen($name) > 80) {
        $errors['name'] = 'Name must be 80 characters or fewer.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
        $errors['email'] = 'Enter a valid email address.';
    }

    if (strlen($password) < 8) {
        $errors['password'] = 'Use at least 8 characters.';
    } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        $errors['password'] = 'Include at least one letter and one number.';
    } elseif (strlen($password) > 72) {
        $errors['password'] = 'Password must be 72 characters or fewer.';
    }

    if ($password !== $confirm) {
        $errors['password_confirm'] = 'Passwords do not match.';
    }

    if (!isset($_POST['terms'])) {
        $errors['terms'] = 'Please accept the terms to continue.';
    }

    // Unique email
    if (!$errors) {
        $stmt = db()->prepare('SELECT 1 FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetchColumn()) {
            $errors['email'] = 'An account with this email already exists.';
        }
    }

    // Create account
    if (!$errors) {
        try {
            $stmt = db()->prepare(
                'INSERT INTO users (name, email, password_hash, created_at) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), now()]);

            login_user(['id' => (int) db()->lastInsertId(), 'last_login_at' => null]);
            flash('success', 'Your account has been created.');
            redirect('dashboard.php');
        } catch (PDOException $ex) {
            // Unique constraint race (two signups at once)
            $errors['email'] = 'An account with this email already exists.';
        }
    }
}

$pageTitle  = 'Create account';
$heading    = 'Create your account';
$subheading = '';
$authAlt    = ['Already have an account?', 'Sign in', 'login.php'];
require __DIR__ . '/includes/auth_header.php';
?>

<form method="post" action="register.php" class="form" novalidate data-loading-form>
    <?= csrf_field() ?>

    <div class="field <?= isset($errors['name']) ? 'has-error' : '' ?>">
        <label for="name">Full name</label>
        <div class="input-wrap">
            <input id="name" name="name" type="text" autocomplete="name"
                   value="<?= e($name) ?>" required maxlength="80" autofocus>
        </div>
        <?php if (isset($errors['name'])): ?><p class="field-error"><?= e($errors['name']) ?></p><?php endif; ?>
    </div>

    <div class="field <?= isset($errors['email']) ? 'has-error' : '' ?>">
        <label for="email">Email address</label>
        <div class="input-wrap">
            <input id="email" name="email" type="email" autocomplete="email"
                   value="<?= e($email) ?>" required maxlength="190">
        </div>
        <?php if (isset($errors['email'])): ?><p class="field-error"><?= e($errors['email']) ?></p><?php endif; ?>
    </div>

    <div class="field <?= isset($errors['password']) ? 'has-error' : '' ?>">
        <label for="password">Password</label>
        <div class="input-wrap">
            <input id="password" name="password" type="password" autocomplete="new-password"
                   required minlength="8" maxlength="72" data-strength-input>
            <button type="button" class="toggle-pass" aria-label="Show password" data-toggle-password>
                <span class="when-hidden"><?= icon('eye') ?></span>
                <span class="when-shown"><?= icon('eye-off') ?></span>
            </button>
        </div>
        <div class="strength" data-strength aria-live="polite">
            <div class="strength-bars"><span></span><span></span><span></span><span></span></div>
            <span class="strength-label">At least 8 characters, including a letter and a number</span>
        </div>
        <?php if (isset($errors['password'])): ?><p class="field-error"><?= e($errors['password']) ?></p><?php endif; ?>
    </div>

    <div class="field <?= isset($errors['password_confirm']) ? 'has-error' : '' ?>">
        <label for="password_confirm">Confirm password</label>
        <div class="input-wrap">
            <input id="password_confirm" name="password_confirm" type="password" autocomplete="new-password"
                   required maxlength="72">
            <button type="button" class="toggle-pass" aria-label="Show password" data-toggle-password>
                <span class="when-hidden"><?= icon('eye') ?></span>
                <span class="when-shown"><?= icon('eye-off') ?></span>
            </button>
        </div>
        <?php if (isset($errors['password_confirm'])): ?><p class="field-error"><?= e($errors['password_confirm']) ?></p><?php endif; ?>
    </div>

    <div class="field <?= isset($errors['terms']) ? 'has-error' : '' ?>">
        <label class="check">
            <input type="checkbox" name="terms" value="1" <?= isset($_POST['terms']) ? 'checked' : '' ?>>
            <span>I agree to the Terms of Service and Privacy Policy</span>
        </label>
        <?php if (isset($errors['terms'])): ?><p class="field-error"><?= e($errors['terms']) ?></p><?php endif; ?>
    </div>

    <button type="submit" class="btn btn-primary btn-block">
        <span class="btn-label">Create account</span>
        <span class="spinner" aria-hidden="true"></span>
    </button>
</form>

<?php require __DIR__ . '/includes/auth_footer.php'; ?>
