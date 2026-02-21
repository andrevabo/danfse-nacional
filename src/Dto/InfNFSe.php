<?php

namespace DanfseNacional\Dto;

readonly class InfNFSe
{
    public function __construct(
        public string $Id = '',
        public string $xLocEmi = '',
        public string $xLocPrestacao = '',
        public string $nNFSe = '',
        public string $cLocIncid = '',
        public string $xLocIncid = '',
        public string $xTribNac = '',
        public string $xTribMun = '',
        public string $verAplic = '',
        public string $ambGer = '',
        public string $tpEmis = '',
        public string $procEmi = '',
        public string $cStat = '',
        public string $dhProc = '',
        public string $nDFSe = '',
        public ?Emitente $emit = null,
        public ?ValoresNFSe $valores = null,
        public ?Dps $DPS = null,
    ) {}
}
