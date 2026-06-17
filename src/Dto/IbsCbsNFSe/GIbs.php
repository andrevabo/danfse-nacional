<?php

namespace DanfseNacional\Dto\IbsCbsNFSe;

readonly class GIbs
{
    public function __construct(
        public string $vIBSTot = '',
        public ?GIbsUfTot $gIBSUFTot = null,
        public ?GIbsMunTot $gIBSMunTot = null,
    ) {}
}
