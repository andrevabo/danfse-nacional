<?php

namespace DanfseNacional\Exception;

use RuntimeException;
use Throwable;

class DanfseMappingException extends RuntimeException
{
    /**
     * @param list<array{
     *     path: string,
     *     message: string,
     *     code: string|null,
     *     value: mixed,
     *     value_type: string
     * }> $errors
     */
    public function __construct(
        string $message,
        private readonly array $errors = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, previous: $previous);
    }

    /**
     * @return list<array{
     *     path: string,
     *     message: string,
     *     code: string|null,
     *     value: mixed,
     *     value_type: string
     * }>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
