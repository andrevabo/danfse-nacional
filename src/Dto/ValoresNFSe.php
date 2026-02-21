<?php

namespace DanfseNacional\Dto;

readonly class ValoresNFSe
{
    public function __construct(
        public string $vLiq = '',
        public string $vBC = '',
        public string $pAliqAplic = '',
        public string $vISSQN = '',
        public string $vTotalRet = '',
        public string $vLiqApurado = '',
    ) {}
}
