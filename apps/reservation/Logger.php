<?php

declare(strict_types=1);

namespace Nkworks\Reservation;

use DateTimeImmutable;
use DateTimeZone;
use JsonException;
use RuntimeException;
use Throwable;

/**
 * アプリケーションログをJSON Lines形式で出力するクラス。
 *
 * 1行につき1件のJSONとして記録するため、
 * 後から検索・集計しやすい形式になっている。
 */
final class Logger
{
    private const LEVEL_DEBUG = 'DEBUG';
    private const LEVEL_INFO = 'INFO';
    private const LEVEL_WARNING = 'WARNING';
    private const LEVEL_ERROR = 'ERROR';
    private const LEVEL_CRITICAL = 'CRITICAL';

    /**
     * ログに出力しない機密情報のキー。
     */
    private const SENSITIVE_KEYS = [
        'password',
        'passwd',
        'db_password',
        'mail_password',
        'smtp_password',
        'secret',
        'secret_key',
        'stripe_secret_key',
        'stripe_webhook_secret',
        'token',
        'access_token',
        'refresh_token',
        'authorization',
        'cookie',
        'session_id',
        'management_token',
        'management_token_hash',
    ];

    private function __construct()
    {
    }

    /**
     * デバッグ情報を記録する。
     *
     * APP_DEBUG=false の場合は出力しない。
     *
     * @param array<string|int, mixed> $context
     */
    public static function debug(
        string $message,
        array $context = []
    ): void {
        if (!Config::bool('APP_DEBUG', false)) {
            return;
        }

        self::write(
            self::LEVEL_DEBUG,
            $message,
            $context
        );
    }

    /**
     * 通常の処理情報を記録する。
     *
     * @param array<string|int, mixed> $context
     */
    public static function info(
        string $message,
        array $context = []
    ): void {
        self::write(
            self::LEVEL_INFO,
            $message,
            $context
        );
    }

    /**
     * 警告を記録する。
     *
     * @param array<string|int, mixed> $context
     */
    public static function warning(
        string $message,
        array $context = []
    ): void {
        self::write(
            self::LEVEL_WARNING,
            $message,
            $context
        );
    }

    /**
     * エラーを記録する。
     *
     * @param array<string|int, mixed> $context
     */
    public static function error(
        string $message,
        array $context = []
    ): void {
        self::write(
            self::LEVEL_ERROR,
            $message,
            $context
        );
    }

    /**
     * システム継続が難しい重大エラーを記録する。
     *
     * @param array<string|int, mixed> $context
     */
    public static function critical(
        string $message,
        array $context = []
    ): void {
        self::write(
            self::LEVEL_CRITICAL,
            $message,
            $context
        );
    }

    /**
     * 例外情報を記録する。
     *
     * @param array<string|int, mixed> $context
     */
    public static function exception(
        Throwable $exception,
        array $context = []
    ): void {
        $exceptionContext = [
            'exception_class' => $exception::class,
            'exception_message' => $exception->getMessage(),
            'exception_code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ];

        self::error(
            'Unhandled exception.',
            array_merge($context, $exceptionContext)
        );
    }

    /**
     * ログをファイルへ出力する。
     *
     * @param array<string|int, mixed> $context
     */
    private static function write(
        string $level,
        string $message,
        array $context
    ): void {
        $logPath = self::resolveLogPath();
        $logDirectory = dirname($logPath);

        self::ensureDirectoryExists($logDirectory);

        $record = [
            'timestamp' => self::currentTimestamp(),
            'level' => $level,
            'message' => $message,
            'context' => self::sanitize($context),
            'request' => self::requestContext(),
        ];

        try {
            $json = json_encode(
                $record,
                JSON_THROW_ON_ERROR
                | JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
                | JSON_INVALID_UTF8_SUBSTITUTE
            );
        } catch (JsonException $exception) {
            throw new RuntimeException(
                'Failed to encode log record.',
                previous: $exception
            );
        }

        $result = file_put_contents(
            $logPath,
            $json . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );

        if ($result === false) {
            throw new RuntimeException(
                sprintf(
                    'Failed to write log file: %s',
                    $logPath
                )
            );
        }
    }

    /**
     * .env の LOG_PATH を絶対パスへ変換する。
     */
    private static function resolveLogPath(): string
    {
        $configuredPath = (string) Config::get(
            'LOG_PATH',
            'storage/logs/app.log'
        );

        if ($configuredPath === '') {
            throw new RuntimeException(
                'LOG_PATH must not be empty.'
            );
        }

        if (self::isAbsolutePath($configuredPath)) {
            return $configuredPath;
        }

        return __DIR__
            . DIRECTORY_SEPARATOR
            . str_replace(
                ['/', '\\'],
                DIRECTORY_SEPARATOR,
                $configuredPath
            );
    }

    /**
     * ディレクトリがなければ作成する。
     */
    private static function ensureDirectoryExists(
        string $directory
    ): void {
        if (is_dir($directory)) {
            return;
        }

        $created = mkdir(
            $directory,
            0775,
            true
        );

        if (!$created && !is_dir($directory)) {
            throw new RuntimeException(
                sprintf(
                    'Failed to create log directory: %s',
                    $directory
                )
            );
        }
    }

    /**
     * 現在日時をISO 8601形式で取得する。
     */
    private static function currentTimestamp(): string
    {
        $timezoneName = (string) Config::get(
            'APP_TIMEZONE',
            'Asia/Tokyo'
        );

        try {
            $timezone = new DateTimeZone($timezoneName);
        } catch (Throwable $exception) {
            throw new RuntimeException(
                sprintf(
                    'Invalid timezone: %s',
                    $timezoneName
                ),
                previous: $exception
            );
        }

        return (
            new DateTimeImmutable('now', $timezone)
        )->format(DATE_ATOM);
    }

    /**
     * HTTPリクエスト情報を取得する。
     *
     * CLI実行時はCLI情報を返す。
     *
     * @return array<string, mixed>
     */
    private static function requestContext(): array
    {
        if (PHP_SAPI === 'cli') {
            return [
                'sapi' => PHP_SAPI,
                'command' => $_SERVER['argv'] ?? [],
            ];
        }

        return [
            'sapi' => PHP_SAPI,
            'method' => $_SERVER['REQUEST_METHOD'] ?? null,
            'uri' => $_SERVER['REQUEST_URI'] ?? null,
            'remote_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'referer' => $_SERVER['HTTP_REFERER'] ?? null,
        ];
    }

    /**
     * ログコンテキストから機密情報を除去する。
     */
    private static function sanitize(
        mixed $value,
        string|int|null $key = null
    ): mixed {
        if (
            is_string($key)
            && self::isSensitiveKey($key)
        ) {
            return '[REDACTED]';
        }

        if (is_array($value)) {
            $sanitized = [];

            foreach ($value as $childKey => $childValue) {
                $sanitized[$childKey] = self::sanitize(
                    $childValue,
                    $childKey
                );
            }

            return $sanitized;
        }

        if (is_object($value)) {
            if ($value instanceof Throwable) {
                return [
                    'exception_class' => $value::class,
                    'message' => $value->getMessage(),
                ];
            }

            return sprintf(
                '[object:%s]',
                $value::class
            );
        }

        if (is_resource($value)) {
            return '[resource]';
        }

        return $value;
    }

    /**
     * 機密情報に該当するキーか判定する。
     */
    private static function isSensitiveKey(string $key): bool
    {
        $normalizedKey = strtolower(
            str_replace(
                ['-', '.', ' '],
                '_',
                $key
            )
        );

        foreach (self::SENSITIVE_KEYS as $sensitiveKey) {
            if (
                $normalizedKey === $sensitiveKey
                || str_ends_with(
                    $normalizedKey,
                    '_' . $sensitiveKey
                )
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * 絶対パスか判定する。
     */
    private static function isAbsolutePath(
        string $path
    ): bool {
        if ($path === '') {
            return false;
        }

        if (
            $path[0] === '/'
            || $path[0] === '\\'
        ) {
            return true;
        }

        return preg_match(
            '/^[A-Za-z]:[\\\\\\/]/',
            $path
        ) === 1;
    }
}