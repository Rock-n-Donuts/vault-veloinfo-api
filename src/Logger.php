<?php
declare(strict_types=1);

namespace Rockndonuts\Hackqc;

use Exception;

class Logger
{
    public const DEFAULT_LOG_PATH = "/logs/";

    /**
     * @param string $message
     * @return void
     */
    public static function log(string $message): void
    {
        if (!APP_PATH || !defined('CUSTOM_LOGS') || CUSTOM_LOGS === false) {
            @error_log($message);
            return;
        }

        $logPath = static::DEFAULT_LOG_PATH;

        if (!empty($_ENV['LOG_PATH'])) {
            $logPath = $_ENV['LOG_PATH'];
        }

        $logFile = APP_PATH.$logPath.'app.log';
        try {
            if (!file_exists($logFile) && !touch($logFile)) {
                error_log($message);
                return;
            }
            file_put_contents($logFile, $message.PHP_EOL, FILE_APPEND);
        } catch (Exception $e) {
            error_log($message);
        }
    }
}