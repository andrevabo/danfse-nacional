<?php

namespace DanfseNacional\Dto\IbsCbsNFSe;

readonly class Mun
{
    public function __construct(
        public string $pIBSMun = '',
        public string $pAliqEfetMun = '',
    ) {}
}
