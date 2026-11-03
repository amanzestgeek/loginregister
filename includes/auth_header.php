<?php
/** Shared top half of the sign-in / sign-up pages.
 *  Expects: $pageTitle, $heading, optional $subheading */
if (!defined('APP_NAME')) { http_response_code(403); exit; }
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> · <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="assets/css/style.css?v=4">
</head>
<body class="auth-body">
<div class="auth">
    <a class="brand auth-brand" href="index.php"><?= brand_mark() ?><span><?= e(APP_NAME) ?></span></a>

    <main class="auth-card">
        <h1 class="auth-title"><?= e($heading) ?></h1>
        <?php if (!empty($subheading)): ?><p class="auth-sub"><?= e($subheading) ?></p><?php endif; ?>
        <?= render_flashes() ?>
