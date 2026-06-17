<?php

namespace DanfseNacional\Dto\IbsCbsNFSe;

readonly class Uf
{
    public function __construct(
        public string $pIBSUF = '',
        public string $pAliqEfetUF = '',
    ) {}
}
