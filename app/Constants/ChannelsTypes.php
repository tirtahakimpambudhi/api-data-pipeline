<?php

namespace App\Constants;

class ChannelsTypes
{
    public const TELEGRAM = 'telegram';
    public const WHATSAPP = 'whatsapp';
    public const DISCORD = 'discord';

    public static function all(): array {
        return [
            self::TELEGRAM,
            self::WHATSAPP,
            self::DISCORD,
        ];
    }
}
