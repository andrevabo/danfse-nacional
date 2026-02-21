<?php

namespace DanfseNacional\Dto;

readonly class Intermediario
{
    public function __construct(
        public string $CNPJ = '',
        public string $CPF = '',
        public string $IMPrestMun = '',
        public string $xNome = '',
        public ?Endereco $end = null,
        public string $fone = '',
        public string $email = '',
    ) {}

    public function documento(): string
    {
        return $this->CNPJ ?: $this->CPF;
    }
}
