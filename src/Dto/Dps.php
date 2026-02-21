<?php

namespace DanfseNacional\Dto;

readonly class Dps
{
    public function __construct(
        public ?InfDPS $infDPS = null,
        public string $versao = '',
    ) {}
}
