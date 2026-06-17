<?php

namespace DanfseNacional\Dto\IbsCbsNFSe;

readonly class Valores
{
    public function __construct(
        public string $vBC = '',
        public string $vCalcReeRepRes = '',
        public ?Uf $uf = null,
        public ?Mun $mun = null,
        public ?Fed $fed = null,
    ) {}
}
