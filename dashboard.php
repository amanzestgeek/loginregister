<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$user = require_login();
$pdo  = db();

/* ---------- Stats (all from the database) ---------- */
$totalUsers = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE last_login_at >= ?');
$stmt->execute([date('Y-m-d') . ' 00:00:00']);
$activeToday = (int) $stmt->fetchColumn();

// Signups per day for the last 7 days (bucketed in PHP so it works on SQLite and MySQL)
$days = [];
for ($i = 6; $i >= 0; $i--) {
    $days[date('Y-m-d', strtotime("-{$i} days"))] = 0;
}
$stmt = $pdo->prepare('SELECT created_at FROM users WHERE created_at >= ?');
$stmt->execute([array_key_first($days) . ' 00:00:00']);
foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $createdAt) {
    $key = substr((string) $createdAt, 0, 10);
    if (isset($days[$key])) {
        $days[$key]++;
    }
}
$newThisWeek = array_sum($days);
$maxDay      = max(1, max($days));

$memberDays    = max(0, (int) floor((time() - strtotime($user['created_at'])) / 86400));
$previousLogin = $_SESSION['previous_login'] ?? null;

$recent = $pdo->query('SELECT id, name, created_at, last_login_at FROM users ORDER BY id DESC LIMIT 8')->fetchAll();

$myColor = avatar_color((string) $user['id']);
$plural  = static fn (int $n, string $word): string => number_format($n) . ' ' . $word . ($n === 1 ? '' : 's');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Overview · <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="assets/css/style.css?v=4">
</head>
<body>
<div class="app">

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-head">
            <a class="brand" href="dashboard.php"><?= brand_mark() ?><span><?= e(APP_NAME) ?></span></a>
        </div>

        <nav class="nav" aria-label="Main">
            <a class="nav-link active" href="#overview"><?= icon('grid', 16) ?> Overview</a>
            <a class="nav-link" href="#signups"><?= icon('chart', 16) ?> Signups</a>
            <a class="nav-link" href="#members"><?= icon('users', 16) ?> Members <span class="nav-count"><?= $totalUsers ?></span></a>
            <a class="nav-link" href="#account"><?= icon('user', 16) ?> Account</a>
        </nav>

        <div class="sidebar-foot">
            <div class="sidebar-user">
                <span class="avatar" style="background: <?= $myColor ?>"><?= e(initials($user['name'])) ?></span>
                <div>
                    <strong><?= e($user['name']) ?></strong>
                    <span><?= e($user['email']) ?></span>
                </div>
            </div>
            <form method="post" action="logout.php">
                <?= csrf_field() ?>
                <button type="submit" class="nav-link"><?= icon('logout', 16) ?> Sign out</button>
            </form>
        </div>
    </aside>
    <div class="sidebar-backdrop" data-close-sidebar></div>

    <div class="main">
        <header class="topbar">
            <button class="icon-btn menu-btn" type="button" aria-label="Open menu" data-toggle-sidebar><?= icon('menu', 18) ?></button>

            <div class="breadcrumb"><?= e(APP_NAME) ?> <span>/</span> <strong>Overview</strong></div>

            <div class="user-menu" data-dropdown>
                <button class="user-menu-btn" type="button" aria-haspopup="true" aria-expanded="false" data-dropdown-toggle>
                    <span class="avatar avatar-sm" style="background: <?= $myColor ?>"><?= e(initials($user['name'])) ?></span>
                    <span class="user-menu-name"><?= e($user['name']) ?></span>
                    <?= icon('chevron-down', 14) ?>
                </button>
                <div class="dropdown" role="menu">
                    <div class="dropdown-head">
                        <strong><?= e($user['name']) ?></strong>
                        <span><?= e($user['email']) ?></span>
                    </div>
                    <a href="#account" class="dropdown-item" role="menuitem"><?= icon('user', 16) ?> Account details</a>
                    <form method="post" action="logout.php">
                        <?= csrf_field() ?>
                        <button type="submit" class="dropdown-item danger" role="menuitem"><?= icon('logout', 16) ?> Sign out</button>
                    </form>
                </div>
            </div>
        </header>

        <main class="content">
            <?= render_flashes() ?>

            <div class="page-head" id="overview">
                <div>
                    <h1>Overview</h1>
                    <p>Welcome, <?= e(first_name($user['name'])) ?>.</p>
                </div>
                <span class="page-date"><?= date('l, j F Y') ?></span>
            </div>

            <section class="stats-grid">
                <article class="stat">
                    <div class="stat-top"><span class="stat-label">Total members</span><?= icon('users', 16) ?></div>
                    <p class="stat-value"><?= number_format($totalUsers) ?></p>
                    <p class="stat-foot">All registered accounts</p>
                </article>
                <article class="stat">
                    <div class="stat-top"><span class="stat-label">New this week</span><?= icon('user-plus', 16) ?></div>
                    <p class="stat-value"><?= number_format($newThisWeek) ?></p>
                    <p class="stat-foot">Last 7 days</p>
                </article>
                <article class="stat">
                    <div class="stat-top"><span class="stat-label">Active today</span><?= icon('clock', 16) ?></div>
                    <p class="stat-value"><?= number_format($activeToday) ?></p>
                    <p class="stat-foot">Signed in since midnight</p>
                </article>
                <article class="stat">
                    <div class="stat-top"><span class="stat-label">Your membership</span><?= icon('calendar', 16) ?></div>
                    <p class="stat-value"><?= $memberDays ?> <small><?= $memberDays === 1 ? 'day' : 'days' ?></small></p>
                    <p class="stat-foot">Joined <?= e(date('j M Y', strtotime($user['created_at']))) ?></p>
                </article>
            </section>

            <section class="grid-2">
                <article class="card" id="signups">
                    <header class="card-head">
                        <div>
                            <h2>Signups</h2>
                            <p>Last 7 days</p>
                        </div>
                        <span class="badge"><?= e($plural($newThisWeek, 'signup')) ?></span>
                    </header>
                    <div class="card-body">
                        <div class="bar-chart" role="img" aria-label="Signups per day for the last 7 days">
                            <?php foreach ($days as $date => $count):
                                $isToday = $date === date('Y-m-d'); ?>
                                <div class="bar-col <?= $isToday ? 'is-today' : '' ?>" title="<?= e(date('D j M', strtotime($date))) ?>: <?= $count ?>">
                                    <span class="bar-value"><?= $count ?></span>
                                    <div class="bar-track">
                                        <div class="bar" style="--h: <?= $count === 0 ? 0 : round($count / $maxDay * 100) ?>%"></div>
                                    </div>
                                    <span class="bar-label"><?= $isToday ? 'Today' : e(date('D', strtotime($date))) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </article>

                <article class="card" id="account">
                    <header class="card-head"><h2>Account details</h2></header>
                    <div class="account-summary">
                        <span class="avatar avatar-lg" style="background: <?= $myColor ?>"><?= e(initials($user['name'])) ?></span>
                        <div>
                            <strong><?= e($user['name']) ?></strong>
                            <span><?= e($user['email']) ?></span>
                        </div>
                    </div>
                    <dl class="details">
                        <div><dt>Member ID</dt><dd>#<?= str_pad((string) $user['id'], 5, '0', STR_PAD_LEFT) ?></dd></div>
                        <div><dt>Joined</dt><dd><?= e(date('j M Y', strtotime($user['created_at']))) ?></dd></div>
                        <div><dt>Previous sign-in</dt><dd><?= $previousLogin ? e(date('j M Y, H:i', strtotime($previousLogin))) : 'First sign-in' ?></dd></div>
                        <div><dt>Status</dt><dd><span class="badge badge-success">Active</span></dd></div>
                    </dl>
                </article>
            </section>

            <section class="card" id="members">
                <header class="card-head">
                    <div>
                        <h2>Recent members</h2>
                        <p>Most recently registered accounts</p>
                    </div>
                    <span class="badge"><?= e($plural($totalUsers, 'member')) ?></span>
                </header>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                        <tr><th>Name</th><th>Joined</th><th>Last sign-in</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($recent as $m): $isMe = (int) $m['id'] === (int) $user['id']; ?>
                            <tr>
                                <td>
                                    <div class="member">
                                        <span class="avatar avatar-sm" style="background: <?= avatar_color((string) $m['id']) ?>"><?= e(initials($m['name'])) ?></span>
                                        <span><?= e($m['name']) ?></span>
                                        <?php if ($isMe): ?><span class="badge">You</span><?php endif; ?>
                                    </div>
                                </td>
                                <td><?= e(date('j M Y', strtotime($m['created_at']))) ?></td>
                                <td><?= e(time_ago($m['last_login_at'])) ?></td>
                                <td><?= $m['last_login_at']
                                        ? '<span class="badge badge-success">Active</span>'
                                        : '<span class="badge">Never signed in</span>' ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <footer class="app-foot">&copy; <?= date('Y') ?> <?= e(APP_NAME) ?></footer>
        </main>
    </div>
</div>
<script src="assets/js/app.js?v=3"></script>
</body>
</html>
