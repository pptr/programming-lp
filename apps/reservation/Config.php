<?php

declare(strict_types=1);

namespace Nkworks\Reservation;

use RuntimeException;

/**
 * 環境変数と .env ファイルを扱う設定クラス。
 *
 * 機密情報や環境依存情報は .env に保存し、
 * 予約期限などの業務設定は system_settings テーブルで管理する。
 */
final class Config
{
    private static bool $loaded = false;

    private function __construct()
    {
    }

    /**
     * .env ファイルを読み込む。
     *
     * すでにサーバー環境変数として設定されている値は上書きしない。
     */
    public static function load(string $envPath): void
    {
        if (self::$loaded) {
            return;
        }

        if (!is_file($envPath)) {
            throw new RuntimeException(
                sprintf('.env file was not found: %s', $envPath)
            );
        }

        if (!is_readable($envPath)) {
            throw new RuntimeException(
                sprintf('.env file is not readable: %s', $envPath)
            );
        }

        $lines = file(
            $envPath,
            FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
        );

        if ($lines === false) {
            throw new RuntimeException(
                sprintf('Failed to read .env file: %s', $envPath)
            );
        }

        foreach ($lines as $lineNumber => $line) {
            self::parseLine($line, $lineNumber + 1);
        }

        self::$loaded = true;
    }

    /**
     * 設定値を取得する。
     */
    public static function get(
        string $key,
        mixed $default = null
    ): mixed {
        $value = $_ENV[$key]
            ?? $_SERVER[$key]
            ?? getenv($key);

        if ($value === false || $value === null || $value === '') {
            return $default;
        }

        return $value;
    }

    /**
     * 必須設定値を文字列として取得する。
     */
    public static function require(string $key): string
    {
        $value = self::get($key);

        if (!is_string($value) || $value === '') {
            throw new RuntimeException(
                sprintf('Required configuration is missing: %s', $key)
            );
        }

        return $value;
    }

    /**
     * 設定値を整数として取得する。
     */
    public static function int(
        string $key,
        int $default = 0
    ): int {
        $value = self::get($key);

        if ($value === null) {
            return $default;
        }

        if (
            filter_var(
                $value,
                FILTER_VALIDATE_INT
            ) === false
        ) {
            throw new RuntimeException(
                sprintf(
                    'Configuration value must be an integer: %s',
                    $key
                )
            );
        }

        return (int) $value;
    }

    /**
     * 設定値を真偽値として取得する。
     */
    public static function bool(
        string $key,
        bool $default = false
    ): bool {
        $value = self::get($key);

        if ($value === null) {
            return $default;
        }

        $parsed = filter_var(
            $value,
            FILTER_VALIDATE_BOOL,
            FILTER_NULL_ON_FAILURE
        );

        if ($parsed === null) {
            throw new RuntimeException(
                sprintf(
                    'Configuration value must be a boolean: %s',
                    $key
                )
            );
        }

        return $parsed;
    }

    /**
     * .env の1行を解析する。
     */
    private static function parseLine(
        string $line,
        int $lineNumber
    ): void {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#')) {
            return;
        }

        if (!str_contains($line, '=')) {
            throw new RuntimeException(
                sprintf(
                    'Invalid .env format at line %d.',
                    $lineNumber
                )
            );
        }

        [$key, $value] = explode('=', $line, 2);

        $key = trim($key);
        $value = trim($value);

        if ($key === '') {
            throw new RuntimeException(
                sprintf(
                    'Empty environment key at line %d.',
                    $lineNumber
                )
            );
        }

        if (!preg_match('/^[A-Z0-9_]+$/', $key)) {
            throw new RuntimeException(
                sprintf(
                    'Invalid environment key "%s" at line %d.',
                    $key,
                    $lineNumber
                )
            );
        }

        $value = self::normalizeValue($value);

        if (self::alreadyExists($key)) {
            return;
        }

        putenv(sprintf('%s=%s', $key, $value));

        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }

    /**
     * 既存の環境変数があるか確認する。
     */
    private static function alreadyExists(string $key): bool
    {
        return array_key_exists($key, $_ENV)
            || array_key_exists($key, $_SERVER)
            || getenv($key) !== false;
    }

    /**
     * .env の値を正規化する。
     */
    private static function normalizeValue(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (strlen($value) >= 2) {
            $firstCharacter = $value[0];
            $lastCharacter = $value[strlen($value) - 1];

            $isDoubleQuoted =
                $firstCharacter === '"'
                && $lastCharacter === '"';

            $isSingleQuoted =
                $firstCharacter === "'"
                && $lastCharacter === "'";

            if ($isDoubleQuoted || $isSingleQuoted) {
                $value = substr($value, 1, -1);
            }
        }

        return match (strtolower($value)) {
            'true' => 'true',
            'false' => 'false',
            'null' => '',
            default => $value,
        };
    }
}