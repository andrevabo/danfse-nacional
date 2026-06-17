<?php

namespace DanfseNacional\Dto\IbsCbsNFSe;

readonly class IbsCbs
{
    public function __construct(
        public string $cLocalidadeIncid = '',
        public string $xLocalidadeIncid = '',
        public ?Valores $valores = null,
        public ?TotCIbs $totCIBS = null,
    ) {}
}
