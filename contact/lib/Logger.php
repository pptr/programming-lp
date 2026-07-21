<?php
declare(strict_types=1);

final class Logger
{
    public static function info(string $message): void { self::write('info', $message); }
    public static function error(string $message): void { self::write('error', $message); }

    private static function write(string $level, string $message): void
    {
        $message = preg_replace('/[\r\n]+/', ' ', $message) ?? 'log message unavailable';
        $line = sprintf("[%s][%s] %s\n", date('Y-m-d H:i:s'), $level, $message);
        $path = CONTACT_ROOT . '/log/contact-' . date('Y-m') . '.log';
        if (@file_put_contents($path, $line, FILE_APPEND | LOCK_EX) === false) {
            error_log($line);
        }
    }
}
