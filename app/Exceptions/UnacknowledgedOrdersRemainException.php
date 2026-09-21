<?php

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class UnacknowledgedOrdersRemainException extends DomainException
{
    public function __construct(string $message = 'This session still has unacknowledged orders. Confirm to end it anyway.')
    {
        parent::__construct('conflict', $message, Response::HTTP_CONFLICT);
    }
}
