<?php

namespace App\Core\Routes;


use App\Core\Controllers\Web\HomeController;
use App\Core\System\Event;

class WebRoute{

    private static $routes = [
        ['GET',  '/', HomeController::class, 'index'],
        ['GET',  "", HomeController::class, 'index']
    ];

    public static function registerRoutes(){
        Event::on(Route::class, 'add-routes', __CLASS__, function($routes){
            return [array_merge($routes, self::$routes)];
        }, 20);
    }
}
