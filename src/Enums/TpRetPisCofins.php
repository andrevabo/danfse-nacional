<?php

namespace DanfseNacional\Enums;

enum TpRetPisCofins: int
{
    case PIS_COFINS_CSLL_NAO_RETIDOS = 0;
    case RETIDO = 1;
    case NAO_RETIDO = 2;
    case PIS_COFINS_CSLL_RETIDOS = 3;
    case PIS_COFINS_RETIDOS_CSLL_NAO_RETIDO = 4;
    case PIS_RETIDO_COFINS_CSLL_NAO_RETIDOS = 5;
    case COFINS_RETIDO_PIS_CSLL_NAO_RETIDOS = 6;
    case PIS_NAO_RETIDO_COFINS_CSLL_RETIDOS = 7;
    case PIS_COFINS_NAO_RETIDOS_CSLL_RETIDO = 8;
    case COFINS_NAO_RETIDO_PIS_CSLL_RETIDOS = 9;

    public function label(): string
    {
        return match ($this) {
            self::PIS_COFINS_CSLL_NAO_RETIDOS => 'PIS/COFINS/CSLL Não Retidos',
            self::RETIDO => 'PIS/COFINS Retido',
            self::NAO_RETIDO => 'PIS/COFINS Não Retido',
            self::PIS_COFINS_CSLL_RETIDOS => 'PIS/COFINS/CSLL Retidos',
            self::PIS_COFINS_RETIDOS_CSLL_NAO_RETIDO => 'PIS/COFINS Retidos, CSLL Não Retido',
            self::PIS_RETIDO_COFINS_CSLL_NAO_RETIDOS => 'PIS Retido, COFINS/CSLL Não Retido',
            self::COFINS_RETIDO_PIS_CSLL_NAO_RETIDOS => 'COFINS Retido, PIS/CSLL Não Retido',
            self::PIS_NAO_RETIDO_COFINS_CSLL_RETIDOS => 'PIS Não Retido, COFINS/CSLL Retidos',
            self::PIS_COFINS_NAO_RETIDOS_CSLL_RETIDO => 'PIS/COFINS Não Retidos, CSLL Retido',
            self::COFINS_NAO_RETIDO_PIS_CSLL_RETIDOS => 'COFINS Não Retido, PIS/CSLL Retidos',
        };
    }

    public static function labelFor(string $value): string
    {
        $case = self::tryFrom((int) $value);
        return $case ? $case->label() : '-';
    }
}
