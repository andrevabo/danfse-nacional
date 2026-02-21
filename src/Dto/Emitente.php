<?php

namespace DanfseNacional\Dto;

readonly class Emitente
{
    public function __construct(
        public string $CNPJ = '',
        public string $CPF = '',
        public string $xNome = '',
        public ?EnderecoEmitente $enderNac = null,
        public string $fone = '',
        public string $email = '',
    ) {}

    public function documento(): string
    {
        return $this->CNPJ ?: $this->CPF;
    }
}
