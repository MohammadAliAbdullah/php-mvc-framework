<?php

namespace App\Plugins\Order\Controllers;

use App\Core\Http\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\View\View;

class OrdersController extends Controller{
    public function __construct(View $view)
    {
        // Pass the View object to the parent Controller constructor
        parent::__construct($view);
    }

    /**
     * Display the dashboard page.
     * Protected by ApiAuthMiddleware if configured in your routes.
     */
    public function index(Request $request): Response
    {
        // Example: gather any data needed for the dashboard
        $variables = [
            'pageTitle' => 'User Dashboard'
        ];

        return $this->renderResponse('orders.index', $variables);
    }
}
