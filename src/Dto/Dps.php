<?php

namespace DanfseNacional\Dto;

readonly class Dps
{
    public function __construct(
        public string $versao = '',
        public ?InfDPS $infDPS = null,
    ) {}
}
