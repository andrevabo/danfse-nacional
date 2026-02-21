<?php

namespace DanfseNacional\Enums;

enum TipoAmbiente: int
{
    case PRODUCAO = 1;
    case HOMOLOGACAO = 2;

    public function label(): string
    {
        return match ($this) {
            self::PRODUCAO => 'Produção',
            self::HOMOLOGACAO => 'Homologação',
        };
    }

    public function isHomologacao(): bool
    {
        return $this === self::HOMOLOGACAO;
    }
}
