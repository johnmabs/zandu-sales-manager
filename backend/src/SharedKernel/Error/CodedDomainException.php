<?php

declare(strict_types=1);

namespace Zandu\SharedKernel\Error;

interface CodedDomainException extends \Throwable
{
    public function errorCode(): string;

    public function publicMessage(): string;
}
