<?php

namespace App\Plugins\Order;

use App\Core\App\Kernel;
use App\Core\System\Event;
use App\Plugins\Order\Routes\WebRoute;
use Illuminate\Container\Container;

class Plugin
{
    protected Container $container;

    protected array $providers = [

    ];

    public function __construct()
    {
        $this->container = Container::getInstance();
    }

    public function getMetadata(): array
    {
        return [
            'name' => 'Sample Plugin',
            'status' => 'active',
            'category' => 'design',
            //... Can add more
        ];
    }

    public function register(): void
    {
        $this->registerProviders();
        WebRoute::registerRoutes();
    }

    public function registerProviders()
    {
        Event::on(Kernel::class, 'add-providers', __CLASS__, function ($providers){
            return [array_merge($this->providers, $providers), 20];
        });
    }
}
