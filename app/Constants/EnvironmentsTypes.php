<?php

namespace App\Constants;

class EnvironmentsTypes
{
    public const PROD = 'production';
    public const DEV = 'development';
    public const STAGING = 'staging';
    public const LOCAL = 'local';
    public const TEST = 'test';

    public static function all(): array {
        return [
            self::PROD,
            self::DEV,
            self::STAGING,
            self::LOCAL,
            self::TEST,
        ];
    }
}
