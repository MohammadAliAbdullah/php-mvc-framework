<?php

declare(strict_types=1);

namespace App\Core\Controllers\Api;

use App\Core\Exceptions\ValidationException;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\ApiController;
use App\Core\Repositories\Post\PostRepositoryInterface;
use App\Core\Models\Post\PostData;
use App\Core\Models\Post\PostResponse;

class PostController extends ApiController
{
    private PostRepositoryInterface $postRepository;
   
    public function __construct(
        PostRepositoryInterface $postRepository,
    )
    {
        parent::__construct();
        $this->postRepository = $postRepository;
    }

    /**
     * Get all posts.
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        $posts = $this->postRepository->findAll();
        return $this->renderResponse($posts);
    }

    /**
     * Get a post by ID.
     *
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function show(Request $request, $id): Response
    {
        $post = $this->postRepository->find((int)$id);
        
        if(!$post){
            return $this->renderError(404, 'Post not found');
        }
        
        return $this->renderResponse($post);
    }

    /**
     * Create a new post.
     *
     * @param Request $request
     * @return Response
     */
    public function create(Request $request): Response
    {
   
        $post = $request->input('post');
        $errors = [];
        $requiredFields = [
            "admin_id",
            "site_id",
            "status",
            "image",
            "comment_status",
            "password",
            "parent",
            "sort_order",
            "type",
            "template",
            "comment_count",
            "views",
            "description",
            "description_one",
            "description_two",
            "description_three",
            "keyline_quote"
        ];
        if(!$post){
            return $this->renderError(404, 'Post not found');
        }
        foreach($requiredFields as $key){
            if(!array_key_exists($key, $post)){
                $errors[] = $key." field is required";
            }
        }
        if(count($errors) > 0){
            return $this->renderError(400, 'Missing required fields', $errors);
        }

        $post = $this->postRepository->create($post);
        if(!$post){
            return $this->renderError(500, 'Failed to create post');
        }
        return $this->renderResponse($post);
    }

    /**
     * Update a post.
     *
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id): Response
    {

        $post = $request->input('post');

        $errors = [];
        $requiredFields = [
            "admin_id",
            "site_id",
            "status",
            "image",
            "comment_status",
            "password",
            "parent",
            "sort_order",
            "type",
            "template",
            "comment_count",
            "views",
            "description",
            "description_one",
            "description_two",
            "description_three",
            "keyline_quote"
        ];
        if(!$post){
            return $this->renderError(404, 'Post not found');
        }
        foreach($post as $key => $value){
            if(!in_array($key, $requiredFields)){
                $errors[] = $key." field is an invalid field";
            }
        }
        if(count($errors) > 0){
            return $this->renderError(400, 'Missing required fields', $errors);
        }
       

        $post = $this->postRepository->update((int)$id, $post);
        if(!$post){
            return $this->renderError(500, 'Failed to update post');
        }
        return $this->renderResponse($post);
    }

    /**
     * Delete a post.
     *
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function delete(Request $request, $id): Response
    {
        $this->postRepository->delete((int) $id);
        return $this->renderResponse(['message' => 'Post deleted successfully']);
    }

} 