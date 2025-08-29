<?php

declare(strict_types=1);

namespace App\Core\Controllers\Web;

use App\Core\Http\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use Exception;
use App\Core\Repositories\Post\PostRepositoryInterface;

/**
 * HomeController handles the home page.
 */
class HomeController extends Controller
{
    private PostRepositoryInterface $postRepository;
    public function __construct(PostRepositoryInterface $postRepository)
    {
        parent::__construct();
        $this->postRepository = $postRepository;
    }

    public function index(): Response
    {
        return $this->renderResponse('index', [
            'title' => 'Mohammed Ali Abdullah',
            'description' => 'A practial test for PHP Fullstack Developer posting',
            'intro' => 'This test designed to select the best candidate for the job. Selected candidate will be working with this framework.',
            'button_text' => 'Create Post',
            'instructions' => [
                '1. Create a new post',
                '2. Add a title, description, and content',
                '3. Save the post',
                '4. View the post',
                '5. Edit the post',
            ]
        ]);
    }

    public function latestPosts(): Response
    {
        $posts = $this->postRepository->getLatestPosts();
        return $this->renderResponse('latest-posts', [
            'posts' => $posts
        ]);
    }

}
