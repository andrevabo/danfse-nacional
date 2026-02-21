<?php

namespace DanfseNacional\Dto;

readonly class TribMunicipal
{
    public function __construct(
        public string $tribISSQN = '',
        public string $tpRetISSQN = '',
        public string $pAliq = '',
        public string $vBC = '',
        public string $vISSQN = '',
        public string $vDescCond = '',
        public string $vDescIncond = '',
        public string $vDeducao = '',
        public string $vOutDed = '',
    ) {}
}
