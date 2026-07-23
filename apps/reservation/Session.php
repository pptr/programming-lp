<?php

declare(strict_types=1);

namespace Nkworks\Reservation;

use RuntimeException;

/**
 * セッション管理クラス。
 *
 * セッション開始、値の取得・保存・削除、
 * セッションIDの再生成を一元管理する。
 */
final class Session
{
    private static bool $started = false;

    private function __construct()
    {
    }

    /**
     * セッションを開始する。
     *
     * すでに開始済みの場合は何もしない。
     */
    public static function start(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        if (headers_sent($file, $line)) {
            throw new RuntimeException(
                sprintf(
                    'Session cannot be started because headers were already sent in %s on line %d.',
                    $file,
                    $line
                )
            );
        }

        self::configureCookie();

        $started = session_start([
            'use_strict_mode' => true,
            'use_only_cookies' => true,
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
        ]);

        if (!$started) {
            throw new RuntimeException(
                'Failed to start session.'
            );
        }

        self::$started = true;
    }

    /**
     * セッション値を保存する。
     */
    public static function set(
        string $key,
        mixed $value
    ): void {
        self::ensureStarted();

        $_SESSION[$key] = $value;
    }

    /**
     * セッション値を取得する。
     */
    public static function get(
        string $key,
        mixed $default = null
    ): mixed {
        self::ensureStarted();

        return $_SESSION[$key] ?? $default;
    }

    /**
     * セッションキーが存在するか確認する。
     */
    public static function has(string $key): bool
    {
        self::ensureStarted();

        return array_key_exists($key, $_SESSION);
    }

    /**
     * セッション値を削除する。
     */
    public static function remove(string $key): void
    {
        self::ensureStarted();

        unset($_SESSION[$key]);
    }

    /**
     * セッション値を取得して削除する。
     *
     * フラッシュメッセージなど、
     * 一度だけ使う値に利用する。
     */
    public static function pull(
        string $key,
        mixed $default = null
    ): mixed {
        self::ensureStarted();

        if (!array_key_exists($key, $_SESSION)) {
            return $default;
        }

        $value = $_SESSION[$key];

        unset($_SESSION[$key]);

        return $value;
    }

    /**
     * セッションIDを再生成する。
     *
     * ログイン成功時や権限変更時に使用する。
     */
    public static function regenerate(): void
    {
        self::ensureStarted();

        if (!session_regenerate_id(true)) {
            throw new RuntimeException(
                'Failed to regenerate session ID.'
            );
        }
    }

    /**
     * セッション内の全データを削除する。
     */
    public static function clear(): void
    {
        self::ensureStarted();

        $_SESSION = [];
    }

    /**
     * セッションを完全に破棄する。
     */
    public static function destroy(): void
    {
        self::ensureStarted();

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                [
                    'expires' => time() - 42000,
                    'path' => $params['path'],
                    'domain' => $params['domain'],
                    'secure' => $params['secure'],
                    'httponly' => $params['httponly'],
                    'samesite' => $params['samesite'] ?? 'Lax',
                ]
            );
        }

        if (!session_destroy()) {
            throw new RuntimeException(
                'Failed to destroy session.'
            );
        }

        self::$started = false;
    }

    /**
     * セッション全体を取得する。
     *
     * デバッグ用途を想定。
     *
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        self::ensureStarted();

        return $_SESSION;
    }

    /**
     * セッションが開始済みか確認する。
     */
    public static function isStarted(): bool
    {
        return self::$started
            || session_status() === PHP_SESSION_ACTIVE;
    }

    /**
     * セッション未開始なら開始する。
     */
    private static function ensureStarted(): void
    {
        if (!self::isStarted()) {
            self::start();
        }
    }

    /**
     * セッションクッキー属性を設定する。
     */
    private static function configureCookie(): void
    {
        $isHttps = self::isHttps();

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    /**
     * HTTPS接続か判定する。
     */
    private static function isHttps(): bool
    {
        if (
            isset($_SERVER['HTTPS'])
            && $_SERVER['HTTPS'] !== ''
            && strtolower((string) $_SERVER['HTTPS']) !== 'off'
        ) {
            return true;
        }

        if (
            isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
            && strtolower(
                (string) $_SERVER['HTTP_X_FORWARDED_PROTO']
            ) === 'https'
        ) {
            return true;
        }

        return false;
    }
}