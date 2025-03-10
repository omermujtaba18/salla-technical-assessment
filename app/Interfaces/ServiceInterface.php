<?php
# This interface will be extended by all services which are responsible for synching products

namespace App\Interfaces;

interface ServiceInterface
{
    public function syncProducts(): void;
    public function getName(): string;
}
