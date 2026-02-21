<?php

namespace DanfseNacional\Enums;

enum OpSimpNac: int
{
    case NAO_OPTANTE = 1;

    case MEI = 2;
    case ME_EPP = 3;

    public function label(): string
    {
        return match ($this) {
            self::NAO_OPTANTE => 'Não Optante',
            self::MEI => 'Optante - Microempreendedor Individual (MEI)',
            self::ME_EPP => 'Optante - Microempresa ou Empresa de Pequeno Porte (ME/EPP)',
        };
    }

    public static function labelFor(string $value): string
    {
        $case = self::tryFrom((int) $value);
        return $case ? $case->label() : '-';
    }
}
