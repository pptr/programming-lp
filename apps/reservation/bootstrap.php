<?php

declare(strict_types=1);

namespace Nkworks\Reservation;

use Throwable;

require_once __DIR__ . '/Config.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Session.php';
require_once __DIR__ . '/Flash.php';

/*
|--------------------------------------------------------------------------
| .env 読み込み
|--------------------------------------------------------------------------
*/

Config::load(__DIR__ . '/.env');

/*
|--------------------------------------------------------------------------
| タイムゾーン
|--------------------------------------------------------------------------
*/

date_default_timezone_set(
    Config::get(
        'APP_TIMEZONE',
        'Asia/Tokyo'
    )
);

/*
|--------------------------------------------------------------------------
| セッション開始
|--------------------------------------------------------------------------
*/

Session::start();

/*
|--------------------------------------------------------------------------
| エラー表示
|--------------------------------------------------------------------------
*/

error_reporting(E_ALL);

if (Config::bool('APP_DEBUG', false)) {

    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');

} else {

    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');

}

/*
|--------------------------------------------------------------------------
| 例外ハンドラ
|--------------------------------------------------------------------------
*/

set_exception_handler(
    static function (Throwable $exception): void {

        http_response_code(500);

        try {
            Logger::exception($exception);
        } catch (Throwable) {
            // ログ出力処理そのものが失敗した場合は何もしない。
        }

        if (Config::bool('APP_DEBUG', false)) {

            echo "<pre>";
            echo htmlspecialchars(
                (string)$exception,
                ENT_QUOTES,
                'UTF-8'
            );
            echo "</pre>";

            return;
        }

        echo "システムエラーが発生しました。";
    }
);