<?php

namespace App\Enums;

enum BackupStorageType: string
{
    case Local = 'local';
    case S3 = 's3';
    case Dropbox = 'dropbox';

    public function toReadableString(): string
    {
        return match ($this) {
            self::Local => __('Local'),
            self::S3 => __('AWS S3'),
            self::Dropbox => __('Dropbox'),
        };
    }

    public static function keyToReadableString(string $typeKey): string
    {
        return match ($typeKey) {
            self::Local->value => __('Local'),
            self::S3->value => __('AWS S3'),
            self::Dropbox->value => __('Dropbox'),
        };
    }

    public static function toSelectOptions(): array
    {
        $selectItems = [];

        $selectItems[self::Local->value] = self::Local->toReadableString();
        // TODO: add in version 2.0
        /* foreach (self::cases() as $case) {
            $selectItems[$case->value] = $case->toReadableString();
        } */

        return $selectItems;
    }

    public static function keyToColor(string $typeKey): string
    {
        return match ($typeKey) {
            self::Local->value => 'warning',
            self::S3->value => 'danger',
            self::Dropbox->value => 'info',
        };
    }
}
