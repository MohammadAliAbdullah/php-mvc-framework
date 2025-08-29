<?php

namespace App\Plugins\Order\Routes;

use App\Core\Middlewares\AuthMiddleware;
use App\Core\Routes\Route;
use App\Core\System\Event;

class ApiRoute{
    static array $routes = [

    ];
    public static function registerRoutes(){
        Event::on(Route::class, 'add-routes', __CLASS__, function($routes){
            return [array_merge($routes, self::$routes)];
        }, 20);
    }
}
