<?php

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class IdempotencyConflictException extends DomainException
{
    public function __construct(string $message = 'This idempotency key was already used with a different order.')
    {
        parent::__construct('idempotency_conflict', $message, Response::HTTP_CONFLICT);
    }
}
