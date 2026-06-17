<?php

namespace DanfseNacional\Dto\IbsCbsNFSe;

readonly class TotCIbs
{
    public function __construct(
        public string $vTotNF = '',
        public ?GIbs $gIBS = null,
        public ?GCbs $gCBS = null
    ) {}
}
