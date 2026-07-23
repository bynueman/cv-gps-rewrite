<?php

namespace App\Enums;

/**
 * Putri Teko packaging forms — used for validation and the fixed
 * catalog display order (botol, kotak, toples, kemasan, besek), not
 * stored as a MySQL ENUM column (easier future schema evolution).
 */
enum TekoPackaging: string
{
    case Botol = "botol";
    case Kotak = "kotak";
    case Toples = "toples";
    case Kemasan = "kemasan";
    case Besek = "besek";

    /** @return string[] */
    public static function displayOrder(): array
    {
        return [
            self::Botol->value,
            self::Kotak->value,
            self::Toples->value,
            self::Kemasan->value,
            self::Besek->value,
        ];
    }
}
