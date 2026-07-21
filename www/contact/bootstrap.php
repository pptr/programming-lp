<?php
declare(strict_types=1);

session_start([
    'cookie_httponly' => true,
    'cookie_secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'cookie_samesite' => 'Lax',
    'use_strict_mode' => true,
]);

const CONTACT_ROOT = __DIR__;
const PROJECT_ROOT = dirname(__DIR__, 2);

require_once CONTACT_ROOT . '/lib/functions.php';
require_once CONTACT_ROOT . '/lib/Logger.php';

$configFile = CONTACT_ROOT . '/config/mail.php';
if (!is_file($configFile)) {
    throw new RuntimeException('メール設定ファイルがありません。config/mail.example.php を複製して mail.php を作成してください。');
}

$mailConfig = require $configFile;
