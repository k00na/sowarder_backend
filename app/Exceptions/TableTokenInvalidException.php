<?php

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class TableTokenInvalidException extends DomainException
{
    public function __construct(string $message = 'Table token is invalid or unavailable.')
    {
        parent::__construct('not_found', $message, Response::HTTP_NOT_FOUND);
    }
}
