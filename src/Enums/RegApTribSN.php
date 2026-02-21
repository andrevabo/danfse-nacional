<?php

namespace DanfseNacional\Enums;

enum RegApTribSN: int
{
    case SN_FEDERAL_MUNICIPAL = 1;
    case SN_FEDERAL_ISSQN_NFSe = 2;
    case NFSe_FEDERAL_MUNICIPAL = 3;

    public function label(): string
    {
        return match ($this) {
            self::SN_FEDERAL_MUNICIPAL => 'Regime de apuração dos tributos federais e municipal pelo Simples Nacional',
            self::SN_FEDERAL_ISSQN_NFSe => 'Regime de apuração dos tributos federais pelo SN e o ISSQN pela NFS-e conforme respectiva legislação municipal do tributo',
            self::NFSe_FEDERAL_MUNICIPAL => 'Regime de apuração dos tributos federais e municipal pela NFS-e conforme respectivas legislações federal e municipal de cada tributo',
        };
    }

    public static function labelFor(string $value): string
    {
        $case = self::tryFrom((int) $value);
        return $case ? $case->label() : '-';
    }
}
