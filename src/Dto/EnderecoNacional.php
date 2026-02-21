<?php

namespace DanfseNacional\Dto;

readonly class EnderecoNacional
{
    public function __construct(
        public string $cMun = '',
        public string $CEP = '',
    ) {}
}
