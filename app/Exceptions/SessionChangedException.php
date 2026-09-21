<?php

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class SessionChangedException extends DomainException
{
    public function __construct(string $message = 'The table session has changed. Reload the menu and try again.')
    {
        parent::__construct('session_changed', $message, Response::HTTP_CONFLICT);
    }
}
