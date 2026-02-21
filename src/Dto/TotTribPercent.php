<?php

namespace DanfseNacional\Dto;

readonly class TotTribPercent
{
    public function __construct(
        public string $pTotTribFed = '',
        public string $pTotTribEst = '',
        public string $pTotTribMun = '',
    ) {}
}
