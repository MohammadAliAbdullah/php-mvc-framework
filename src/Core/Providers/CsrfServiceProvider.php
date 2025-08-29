<?php

declare(strict_types=1);

namespace App\Core\Providers;

use Illuminate\Container\Container;
use App\Core\Services\CsrfService;

class CsrfServiceProvider
{
    public function __construct(private Container $container)
    {
    }

    public function register(): void
    {
        $this->container->bind(CsrfService::class, function () {
            return new CsrfService();
        });
    }
}
