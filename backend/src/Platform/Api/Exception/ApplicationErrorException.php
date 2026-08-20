<?php

declare(strict_types=1);

namespace Zandu\Platform\Api\Exception;

use RuntimeException;
use Zandu\SharedKernel\Error\DomainError;

final class ApplicationErrorException extends RuntimeException
{
    public function __construct(
        private readonly DomainError $error,
        private readonly int $statusCode = 422,
    ) {
        parent::__construct($error->message());
    }

    public function error(): DomainError
    {
        return $this->error;
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }
}
