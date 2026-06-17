<?php

namespace DanfseNacional\Dto\IbsCbsDps;

readonly class IbsCbs
{
    public function __construct(
        public string $finNFSe = '',
        public string $indFinal = '',
        public string $cIndOp = '',
        public string $indDest = '',
        public ?Valores $valores = null,
    ) {}
}
