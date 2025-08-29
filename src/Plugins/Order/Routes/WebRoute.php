<?php

namespace App\Plugins\Order\Routes;

use App\Core\Middlewares\AuthMiddleware;
use App\Core\Routes\Route;
use App\Core\System\Event;
use App\Plugins\Order\Controllers\OrdersController;

class WebRoute{
    static array $routes = [
        ['GET',  '/orders', OrdersController::class, 'index', [], 'Order'],
    ];
    public static function registerRoutes(){
        Event::on(Route::class, 'add-routes', __CLASS__, function($routes){
            return [array_merge($routes, self::$routes)];
        }, 20);
    }
}
