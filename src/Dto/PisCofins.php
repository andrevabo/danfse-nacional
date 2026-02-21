<?php

namespace DanfseNacional\Dto;

readonly class PisCofins
{
    public function __construct(
        public string $CST = '',
        public string $vBCPisCofins = '',
        public string $pAliqPis = '',
        public string $pAliqCofins = '',
        public string $vPis = '',
        public string $vCofins = '',
    ) {}
}
