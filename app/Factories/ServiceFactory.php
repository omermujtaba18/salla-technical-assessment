<?php

namespace App\Factories;

use App\Interfaces\ServiceInterface;
use InvalidArgumentException;

class ServiceFactory
{
    public static function create(string $service): ServiceInterface
    {
        $class = "App\\Services\\$service";

        if (!class_exists($class)) {
            throw new InvalidArgumentException("Service [$service] not found.");
        }

        return app($class);
    }
}
