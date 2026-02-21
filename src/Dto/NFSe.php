<?php

namespace DanfseNacional\Dto;

readonly class NFSe
{
    public function __construct(
        public ?InfNFSe $infNFSe = null,
        public string $versao = '',
    ) {}
}
