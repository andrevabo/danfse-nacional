<?php

namespace DanfseNacional\Dto\IbsCbsDps;

readonly class GIbsCbs
{
    public function __construct(
        public string $CST = '',
        public string $cClassTrib = '',
    ) {}
}
