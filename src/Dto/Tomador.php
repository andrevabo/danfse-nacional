<?php

namespace DanfseNacional\Dto;

readonly class Tomador
{
    public function __construct(
        public string $CNPJ = '',
        public string $CPF = '',
        public string $IM = '',
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
