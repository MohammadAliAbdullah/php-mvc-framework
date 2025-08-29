<?php

declare(strict_types=1);

namespace App\Core\Controllers;

use App\Core\Http\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\View\View;

/**
 * DashboardController handles the main "dashboard" page, which is
 * typically restricted to authenticated users.
 */
class DashboardController extends Controller
{
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
            'pageTitle' => 'User Dashboard',
            // 'user' => $someUserModelOrData,
            // 'stats' => $someStatsArray,
        ];

        // Render the "dashboard" view with the data
        return $this->renderResponse('dashboard', $variables);
    }
}
