<?php

namespace DanfseNacional\Enums;

enum TribISSQN: int
{
    case TRIBUTAVEL = 1;
    case IMUNIDADE = 2;
    case EXPORTACAO = 3;
    case NAO_INCIDENCIA = 4;

    public function label(): string
    {
        return match ($this) {
            self::TRIBUTAVEL => 'Operação Tributável',
            self::IMUNIDADE => 'Imunidade',
            self::EXPORTACAO => 'Exportação de Serviço',
            self::NAO_INCIDENCIA => 'Não Incidência',
        };
    }

    public static function labelFor(string $value): string
    {
        $case = self::tryFrom((int) $value);
        return $case ? $case->label() : '-';
    }
}
