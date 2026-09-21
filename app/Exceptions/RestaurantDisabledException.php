<?php

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class RestaurantDisabledException extends DomainException
{
    public function __construct(string $message = 'This restaurant is currently disabled.')
    {
        parent::__construct('restaurant_disabled', $message, Response::HTTP_FORBIDDEN);
    }
}
