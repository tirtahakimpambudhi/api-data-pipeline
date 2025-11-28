<?php

namespace App\Constants;

use App\Traits\Helpers;

class RolesTypes
{
    use Helpers;
    public const ALMIGHTY = 'admin';
    public const SLAVE    = 'user';


    public static function all(): array
    {
        return [
            self::ALMIGHTY,
            self::SLAVE,
        ];
    }

    public static function permissions(string $role): array
    {
        if ($role === RolesTypes::ALMIGHTY) {
            return [...(new RolesTypes)->crossComboArr(ResourcesTypes::all(), ActionsTypes::all(), 'resource_type', 'action')];
        }
        if ($role === RolesTypes::SLAVE) {
            return [...(new RolesTypes)->crossComboArr([ResourcesTypes::CONFIGURATIONS], ActionsTypes::all(), 'resource_type', 'action'),
                [
                    'resource_type' =>  ResourcesTypes::SERVICES_ENVIRONMENTS,
                    'action' => ActionsTypes::READ
                ],
                [
                    'resource_type' =>  ResourcesTypes::CHANNELS,
                    'action' => ActionsTypes::READ
                ]
            ];
        }
        return [];
    }
}
