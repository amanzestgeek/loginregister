<?php
declare(strict_types=1);

/* ==========================================================
 |  App configuration
 * ========================================================== */
const APP_NAME     = 'Lumina';
const APP_TIMEZONE = 'UTC';          // e.g. 'Asia/Kolkata'

/*
 | Database
 | 'sqlite' works out of the box (file is created automatically).
 | To use MySQL: set DB_DRIVER to 'mysql', create the database
 | (CREATE DATABASE php_users;) and fill in the credentials below.
 | The users table is created automatically on first run.
 */
const DB_DRIVER      = 'sqlite';     // 'sqlite' | 'mysql'
const DB_SQLITE_PATH = __DIR__ . '/../data/app.sqlite';
const DB_HOST        = '127.0.0.1';
const DB_PORT        = 3306;
const DB_NAME        = 'php_users';
const DB_USER        = 'root';
const DB_PASS        = '';

/* Login throttling */
const LOGIN_MAX_ATTEMPTS = 5;
const LOGIN_LOCK_SECONDS = 60;

date_default_timezone_set(APP_TIMEZONE);

/* Security headers */
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

/* Hardened session */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('lumina_sid');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    session_start();
}

require __DIR__ . '/functions.php';
require __DIR__ . '/icons.php';
