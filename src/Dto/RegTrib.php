<?php

namespace DanfseNacional\Dto;

readonly class RegTrib
{
    public function __construct(
        public string $opSimpNac = '',
        public string $regApTribSN = '',
        public string $regEspTrib = '',
    ) {}
}
