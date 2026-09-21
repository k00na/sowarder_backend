<?php

use App\Broadcasting\RestaurantChannel;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('restaurant.{restaurantId}', RestaurantChannel::class);
