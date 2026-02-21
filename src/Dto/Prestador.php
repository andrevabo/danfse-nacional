<?php

namespace DanfseNacional\Dto;

readonly class Prestador
{
    public function __construct(
        public string $CNPJ = '',
        public string $CPF = '',
        public ?RegTrib $regTrib = null,
        public string $fone = '',
        public string $email = '',
    ) {}

    public function documento(): string
    {
        return $this->CNPJ ?: $this->CPF;
    }
}
