<?php

declare(strict_types=1);

namespace App\Core\Providers;

use App\Core\Exceptions\AccessDeniedHttpException;
use App\Core\Exceptions\BadRequestHttpException;
use App\Core\Exceptions\ConflictHttpException;
use App\Core\Exceptions\GoneHttpException;
use App\Core\Exceptions\HttpException;
use App\Core\Exceptions\HttpExceptionInterface;
use App\Core\Exceptions\MethodNotAllowedHttpException;
use App\Core\Exceptions\NotFoundHttpException;
use App\Core\Exceptions\ServiceUnavailableHttpException;
use App\Core\Exceptions\TooManyRequestsHttpException;
use App\Core\Exceptions\UnauthorizedHttpException;
use Illuminate\Container\Container;

readonly class ExceptionServiceProvider
{
    public function __construct(private Container $container)
    {
    }

    public function register(): void
    {
        /**
         * Bind the interface to the base HttpException as a fallback.
         * If you ever ask for HttpExceptionInterface, it will give you HttpException.
         */
        $this->container->bind(HttpExceptionInterface::class, HttpException::class);

        /**
         * Bind each exception to itself. This means if the container is asked for
         * one of these classes, it can instantiate it. Not typically needed for exceptions,
         * but provided here for completeness.
         */
        $this->container->bind(HttpException::class, HttpException::class);
        $this->container->bind(AccessDeniedHttpException::class, AccessDeniedHttpException::class);
        $this->container->bind(BadRequestHttpException::class, BadRequestHttpException::class);
        $this->container->bind(ConflictHttpException::class, ConflictHttpException::class);
        $this->container->bind(GoneHttpException::class, GoneHttpException::class);
        $this->container->bind(MethodNotAllowedHttpException::class, MethodNotAllowedHttpException::class);
        $this->container->bind(NotFoundHttpException::class, NotFoundHttpException::class);
        $this->container->bind(ServiceUnavailableHttpException::class, ServiceUnavailableHttpException::class);
        $this->container->bind(TooManyRequestsHttpException::class, TooManyRequestsHttpException::class);
        $this->container->bind(UnauthorizedHttpException::class, UnauthorizedHttpException::class);
    }
}
