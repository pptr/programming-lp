<?php

declare(strict_types=1);

namespace Nkworks\Reservation;

use PDO;
use PDOException;
use RuntimeException;
use Throwable;

/**
 * PDO接続管理クラス
 */
final class Database
{
    private static ?PDO $connection = null;

    private function __construct()
    {
    }

    /**
     * PDOインスタンス取得
     */
    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            Config::require('DB_HOST'),
            Config::int('DB_PORT', 3306),
            Config::require('DB_NAME'),
            Config::get('DB_CHARSET', 'utf8mb4')
        );

        try {
            self::$connection = new PDO(
                $dsn,
                Config::require('DB_USER'),
                Config::require('DB_PASSWORD'),
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::ATTR_STRINGIFY_FETCHES  => false,
                    PDO::ATTR_PERSISTENT         => false,
                ]
            );

            // 日本時間固定
            self::$connection->exec("SET time_zone = '+09:00'");

            return self::$connection;

        } catch (PDOException $e) {
            throw new RuntimeException(
                'Database connection failed.',
                previous: $e
            );
        }
    }

    /**
     * トランザクション実行
     *
     * 使用例:
     *
     * Database::transaction(function(PDO $pdo){
     *      ...
     * });
     */
    public static function transaction(callable $callback): mixed
    {
        $pdo = self::connection();

        try {

            $pdo->beginTransaction();

            $result = $callback($pdo);

            $pdo->commit();

            return $result;

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }
    }

    /**
     * SELECT 1件取得
     */
    public static function fetchOne(
        string $sql,
        array $params = []
    ): array|null {

        $stmt = self::connection()->prepare($sql);

        $stmt->execute($params);

        $result = $stmt->fetch();

        return $result === false
            ? null
            : $result;
    }

    /**
     * SELECT 複数件取得
     */
    public static function fetchAll(
        string $sql,
        array $params = []
    ): array {

        $stmt = self::connection()->prepare($sql);

        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * INSERT / UPDATE / DELETE
     *
     * 戻り値:
     * 更新件数
     */
    public static function execute(
        string $sql,
        array $params = []
    ): int {

        $stmt = self::connection()->prepare($sql);

        $stmt->execute($params);

        return $stmt->rowCount();
    }

    /**
     * 最後のAUTO_INCREMENT取得
     */
    public static function lastInsertId(): int
    {
        return (int) self::connection()->lastInsertId();
    }

    /**
     * 接続解除
     */
    public static function disconnect(): void
    {
        self::$connection = null;
    }
}