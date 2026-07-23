<?php

declare(strict_types=1);

namespace Nkworks\Reservation;

/**
 * フラッシュメッセージ管理クラス。
 *
 * リダイレクト後に一度だけ表示するメッセージを管理する。
 */
final class Flash
{
    private const SESSION_KEY = '_flash';

    private function __construct()
    {
    }

    /**
     * 成功メッセージを登録する。
     */
    public static function success(string $message): void
    {
        self::add('success', $message);
    }

    /**
     * エラーメッセージを登録する。
     */
    public static function error(string $message): void
    {
        self::add('error', $message);
    }

    /**
     * 警告メッセージを登録する。
     */
    public static function warning(string $message): void
    {
        self::add('warning', $message);
    }

    /**
     * 情報メッセージを登録する。
     */
    public static function info(string $message): void
    {
        self::add('info', $message);
    }

    /**
     * 任意の種類のメッセージを登録する。
     */
    public static function add(
        string $type,
        string $message
    ): void {
        $messages = Session::get(
            self::SESSION_KEY,
            []
        );

        $messages[$type][] = $message;

        Session::set(
            self::SESSION_KEY,
            $messages
        );
    }

    /**
     * 指定種類のメッセージを取得して削除する。
     *
     * @return string[]
     */
    public static function get(string $type): array
    {
        $messages = Session::get(
            self::SESSION_KEY,
            []
        );

        $result = $messages[$type] ?? [];

        unset($messages[$type]);

        if (empty($messages)) {
            Session::remove(self::SESSION_KEY);
        } else {
            Session::set(
                self::SESSION_KEY,
                $messages
            );
        }

        return $result;
    }

    /**
     * 全メッセージを取得して削除する。
     *
     * @return array<string, string[]>
     */
    public static function all(): array
    {
        return Session::pull(
            self::SESSION_KEY,
            []
        );
    }

    /**
     * メッセージが存在するか。
     */
    public static function has(
        ?string $type = null
    ): bool {
        $messages = Session::get(
            self::SESSION_KEY,
            []
        );

        if ($type === null) {
            return !empty($messages);
        }

        return !empty($messages[$type]);
    }

    /**
     * 全メッセージを削除する。
     */
    public static function clear(): void
    {
        Session::remove(
            self::SESSION_KEY
        );
    }
}