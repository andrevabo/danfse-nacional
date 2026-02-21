<?php

namespace DanfseNacional\Dto;

readonly class TribFederal
{
    public function __construct(
        public ?PisCofins $piscofins = null,
        public string $vRetCP = '',
        public string $vRetIRRF = '',
        public string $vRetCSLL = '',
    ) {}
}
