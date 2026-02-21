<?php

namespace DanfseNacional\Dto;

readonly class Valores
{
    public function __construct(
        public ?VServPrest $vServPrest = null,
        public ?Tributacao $trib = null,
        public string $vDescCondIncond = '',
    ) {}
}
