<?php

namespace DanfseNacional\Enums;

enum TpRetISSQN: int
{
    case NAO_RETIDO = 1;
    case RETIDO_TOMADOR = 2;
    case RETIDO_INTERMEDIARIO = 3;

    public function label(): string
    {
        return match ($this) {
            self::NAO_RETIDO => 'Não Retido',
            self::RETIDO_TOMADOR => 'Retido pelo Tomador',
            self::RETIDO_INTERMEDIARIO => 'Retido pelo Intermediário',
        };
    }

    public static function labelFor(string $value): string
    {
        $case = self::tryFrom((int) $value);
        return $case ? $case->label() : '-';
    }
}
