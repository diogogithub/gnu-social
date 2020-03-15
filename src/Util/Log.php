<?php

namespace App\Util;

use Psr\Log\LoggerInterface;

class Log
{
    private static ?LoggerInterface $logger;

    public static function setLogger($l): void
    {
        self::$logger = $l;
    }

    public static function error(string $msg): void
    {
        self::$logger->error($msg);
    }

    public static function info(string $msg): void
    {
        self::$logger->info($msg);
    }

    public static function debug(string $msg): void
    {
        $logger->debug($msg);
    }
}
