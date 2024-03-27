<?php

namespace App\Enums;

enum Status: string
{
    case Connected = 'connected';
    case Disconnected = 'disconnected';

    public function toReadableString(): string
    {
        return match ($this) {
            self::Connected => __('Connected'),
            self::Disconnected => __('Disconnected'),
        };
    }

    public function toDisplayColor(): string
    {
        return match ($this) {
            self::Connected => 'success',
            self::Disconnected => 'danger',
        };
    }
}
