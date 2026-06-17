<?php

namespace DanfseNacional\Dto\IbsCbsDps;

readonly class Trib
{
    public function __construct(
        public ?GIbsCbs $gIBSCBS = null,
    ) {}
}
