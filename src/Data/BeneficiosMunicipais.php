<?php

namespace DanfseNacional\Data;

final class BeneficiosMunicipais
{
    private const MAP = [
        3205309 => [
            '32053090200004' => 'Alíquota diferenciada - Redução de alíquota - Centro (Art. 32 da Lei nº 6.075/2003 e Decreto nº 13.314/2007',
            '32053090200028' => 'Redução da Base de Cálculo - Dedução - Informática (Art. 33, Lei 6.075/2003)'
        ],
    ];

    public static function lookup(string|int $cMun, ?string $nBM): ?string
    {
        if (!$nBM) {
            return null;
        }

        $code = (int) $cMun;
        $m = self::MAP[$code] ?? null;
        $b = $m[$nBM] ?? null;

        return $nBM . (is_null($b) ? null : ' - ' . $b);
    }
}
