<?php

namespace App\Core\Routes;

use App\Core\Controllers\AuthController;
use App\Core\Controllers\Web\HomeController;
use App\Core\Controllers\Web\AboutController;
use App\Core\Controllers\Web\BlogController;
use App\Core\Controllers\Web\PageController;
use App\Core\Controllers\Web\ProductController;
use App\Core\Controllers\Web\CategoryController;
use App\Core\Controllers\Web\CatalogueController;
use App\Core\Controllers\Web\DashboardController;
use App\Core\Controllers\Web\AccountController;
use App\Core\Controllers\Web\ContactController;
use App\Core\Controllers\Web\DomController;
use App\Core\Controllers\Web\ProjectController;
use App\Core\Controllers\Web\SolutionController;
use App\Core\Controllers\Web\LoginController;
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
