<?php

namespace DanfseNacional\Dto;

readonly class CServ
{
    public function __construct(
        public string $cTribNac = '',
        public string $cTribMun = '',
        public string $xDescServ = '',
        public string $cNBS = '',
    ) {}
}
