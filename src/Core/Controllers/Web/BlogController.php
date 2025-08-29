<?php

declare(strict_types=1);

namespace App\Core\Controllers\Web;

use App\Core\Http\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use Exception;

/**
 * BlogController handles the blog page.
 */
class BlogController extends Controller
{

    public function __construct()
    {
        parent::__construct();
    }

    public function index(): Response
    {
        return $this->renderResponse('index');
    }

    public function detail(Request $request, $slug): Response
    {
        return $this->renderResponse('detail', [
            'slug' => $slug
        ]);
    }
}
