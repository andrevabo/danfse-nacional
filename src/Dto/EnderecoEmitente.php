<?php

namespace DanfseNacional\Dto;

readonly class EnderecoEmitente
{
    public function __construct(
        public string $xLgr = '',
        public string $nro = '',
        public string $xBairro = '',
        public string $cMun = '',
        public string $UF = '',
        public string $CEP = '',
    ) {}
}
