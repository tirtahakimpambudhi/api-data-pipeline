<?php

namespace App\Constants;

class RolesTypes
{
    public const ALMIGHTY = 'almighty';
    public const SLAVE    = 'slave';


    public static function all(): array
    {
        return [
            self::ALMIGHTY,
            self::SLAVE,
        ];
    }
}
