<?php

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class SessionAlreadyEndedException extends DomainException
{
    public function __construct(string $message = 'This session has already ended.')
    {
        parent::__construct('conflict', $message, Response::HTTP_CONFLICT);
    }
}
