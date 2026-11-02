<?php
// SMTP settings. The real values live in mail_config.local.php, which is not in Git.
// Copy mail_config.local.example.php to mail_config.local.php and fill it in.
if (!defined('MAIL_USERNAME')) {
    $local = __DIR__ . '/mail_config.local.php';
    $cfg = file_exists($local) ? require $local : [];
    define('MAIL_USERNAME', $cfg['username'] ?? '');
    define('MAIL_PASSWORD', $cfg['password'] ?? '');
}
