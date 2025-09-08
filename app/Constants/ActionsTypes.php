<?php

namespace App\Constants;

class ActionsTypes
{
    public const CREATE = 'create';
    public const READ = 'read';
    public const UPDATE = 'update';
    public const DELETE = 'delete';

    public static function all(): array {
        return [
            self::CREATE,
            self::READ,
            self::UPDATE,
            self::DELETE,
        ];
    }
}
