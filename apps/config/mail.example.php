<?php
declare(strict_types=1);

return [
    'smtp' => [
        'host' => '初期ドメイン.sakura.ne.jp',
        'port' => 587,
        'secure' => 'tls',
        'username' => 'info@nkworks.info',
        'password' => 'ここにメールパスワードを設定',
    ],
    'from' => [
        'address' => 'info@nkworks.info',
        'name' => 'NK Works',
    ],
    'admin' => [
        'address' => 'info@nkworks.info',
        'name' => 'NK Works',
    ],
    'turnstile' => [
        'enabled' => false,
        'site_key' => '',
        'secret_key' => '',
    ],
];
