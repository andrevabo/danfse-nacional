<?php

namespace DanfseNacional\Dto\IbsCbsNFSe;

readonly class Fed
{
    public function __construct(
        public string $pCBS = '',
        public string $pAliqEfetCBS = '',
    ) {}
}
