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
use App\Core\Repositories\Media\MediaRepositoryInterface;
use App\Core\Repositories\Post\PostStatusRepositoryInterface;
use League\Csv\Reader;

class PostController extends ApiController
{
    private PostRepositoryInterface $postRepository;
    private PostStatusRepositoryInterface $postStatusRepository;
    private MediaRepositoryInterface $mediaRepository;
    public function __construct(
        PostRepositoryInterface $postRepository,
        PostStatusRepositoryInterface $postStatusRepository,
        MediaRepositoryInterface $mediaRepository
    )
    {
        parent::__construct();
        $this->postRepository = $postRepository;
        $this->postStatusRepository = $postStatusRepository;
        $this->mediaRepository = $mediaRepository;
    }

    /**
     * Get all posts.
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        $posts = $this->postRepository->getAll();
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
        $post = $this->postRepository->showPost((int)$id);
        if(!$post){
            return $this->renderError(404, 'Post not found');
        }
        $response = new PostResponse($post->data);
        return $this->renderResponse($response);
    }

    /**
     * Create a new post.
     *
     * @param Request $request
     * @return Response
     */
    public function create(Request $request): Response
    {
        try {
            $post = $request->input('post');
            $postData = new PostData($post);
        } catch (ValidationException $e) {
            return $this->renderError(422, $e->getMessage(), $e->getErrors());
        }

        $post = $this->postRepository ->createPost($postData);
        if(!$post){
            return $this->renderError(500, 'Failed to create post');
        }
        $post = new PostResponse($post->data);
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
        try {
            $post = $request->input('post');
            $postData = new PostData($post);
        } catch (ValidationException $e) {
            return $this->renderError(422, $e->getMessage(), $e->getErrors());
        }

        $post = $this->postRepository->updatePost($postData);
        if(!$post){
            return $this->renderError(500, 'Failed to update post');
        }
        $post = new PostResponse($post->data);
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


    public function getStatuses(Request $request): Response
    {
        $statuses = $this->postStatusRepository->findAll();
        return $this->renderResponse($statuses);
    }

    public function upload(Request $request, int $post_id): Response
    {
        $property = $request->input('property');
        
        // Set default size
        $size = [
            'width' => 945,
            'height' => 630,
        ];
        
        // Override size based on property
        if($property === 'banner_image'){
            $size = [
                'width' => 1600,
                'height' => 657,
            ];
        }
        elseif ($property === 'main_image_one') {
            $size = [
                'width' => 1341,
                'height' => 608,
            ];
        } elseif ($property === 'main_image_two') {
            $size = [
                'width' => 670,
                'height' => 686,
            ];
        } elseif($property === 'featured_image'){
            $size['featured_image_one'] = [
                'width' => 691,
                'height' => 461,
            ];
            $size['featured_image_two'] = [
                'width' => 537,
                'height' => 501,
            ];
        }
        
        if($request->files() || isset($_FILES['files'])){
          $files = $request->files() ?? $_FILES['files'];
          
          if(!count($files)){
            return $this->renderError(422, 'No files uploaded');
          }
          $data = [
            'files' => $files,
            'upload_dir' => $request->input('upload_dir', 'media/Projects')
          ];

          $result = $this->mediaRepository->upload($data, $size, 'media/Projects');
          if(!$result){
            return $this->renderError(500, 'Failed to upload media');
          }
          if(isset($result['files'])){
              $this->postRepository->insertPostImages($result['files'], $post_id);
          }
          return $this->renderResponse($result);
        }

        return $this->renderError(422, 'No files uploaded');
    }
    public function deletePostImage(Request $request, int $post_image_id): Response
    {
        $deleted = $this->postRepository->deletePostImage($post_image_id);
        return $this->renderResponse(['message' => 'Media deleted successfully', 'deleted' => $deleted]);
    }

    public function importPosts(Request $request): Response
    {
        $csv_file = $request->file('csv_file');
        $csv_file_path = $csv_file['tmp_name'] ?? $csv_file['name'] ?? '';
        if (empty($csv_file_path)) {
            return $this->renderError(400, 'No CSV file uploaded or file path not found');
        }

        $result = $this->postRepository->importPosts($csv_file_path);
        return $this->renderResponse(['success' => $result]);
    }

    public function importPostImages(Request $request): Response
    {
        $csv_file = $request->file('csv_file');
        
        // Extract the file path from the uploaded file array
        $csv_file_path = $csv_file['tmp_name'] ?? $csv_file['name'] ?? '';
        
        if (empty($csv_file_path)) {
            return $this->renderError(400, 'No CSV file uploaded or file path not found');
        }
        
        $posts = $this->postRepository->importPostImages($csv_file_path);
        return $this->renderResponse(['success' => $posts]);
    }

} 