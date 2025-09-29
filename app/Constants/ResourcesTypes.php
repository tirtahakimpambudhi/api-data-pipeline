<?php

namespace App\Constants;

class ResourcesTypes
{

    public const CHANNELS = 'channels';
    public const CONFIGURATIONS = 'configurations';
    public const ENVIRONMENTS = 'environments';
    public const NAMESPACES = 'namespaces';
    public const SERVICES = 'services';
    public const SERVICES_ENVIRONMENTS  = 'services_environments';
    public const PERMISSIONS = 'permissions';
    public const ROLES = 'roles';
    public const  ROLES_PERMISSIONS = 'roles_permissions';
    public const USERS = 'users';
    public static function all(): array
    {
        return [
            self::CHANNELS,
            self::CONFIGURATIONS,
            self::ENVIRONMENTS,
            self::NAMESPACES,
            self::SERVICES,
            self::SERVICES_ENVIRONMENTS,
            self::PERMISSIONS,
            self::ROLES,
            self::ROLES_PERMISSIONS,
            self::USERS,
        ];
    }
}
