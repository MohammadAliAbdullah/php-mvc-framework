<?php

declare(strict_types=1);

namespace App\Core\Controllers;

use App\Core\Http\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Repositories\Auth\ScopeRepositoryInterface;
use App\Core\Repositories\UserRepositoryInterface;
use App\Core\Services\AuthService;
use App\Core\Services\CsrfService;
use Exception;

/**
 * AuthController handles user authentication and registration.
 */
class AuthController extends Controller
{
    private AuthService $authService;
    private UserRepositoryInterface $userRepository;
    private ScopeRepositoryInterface $scopeRepository;
    private CsrfService $csrfService;

    public function __construct(
        AuthService $authService,
        UserRepositoryInterface $userRepository,
        ScopeRepositoryInterface $scopeRepository,
        CsrfService $csrfService
    ) {
        parent::__construct();
        $this->authService = $authService;
        $this->userRepository = $userRepository;
        $this->scopeRepository = $scopeRepository;
        $this->csrfService = $csrfService;
    }

    /**
     * Display the registration form.
     */
    public function showRegistrationForm(): Response
    {
        return $this->renderResponse('register', [
            'pageTitle' => 'User Registration',
            'message' => '',
        ]);
    }

    public function registerAdminForm(): Response
    {
        return $this->renderResponse('register-admin', [
            'pageTitle' => 'Admin Registration',
            'message' => '',
        ]);
    }
    public function registerCustomerForm(): Response
    {
        return $this->renderResponse('register-customer', [
            'pageTitle' => 'Customer Registration',
            'message' => '',
        ]);
    }
    public function registerVendorForm(): Response
    {
        return $this->renderResponse('register-vendor', [
            'pageTitle' => 'Vendor Registration',
            'message' => '',
        ]);
    }
    public function registerAdmin(Request $request): Response
    {
        $data = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|min:8',
            'confirm_password' => 'required|same:password',
        ]);
        return $this->renderResponse('register-admin', [
            'pageTitle' => 'Admin Registration',
            'message' => '',
        ]);
    }
    public function registerCustomer(Request $request): Response
    {
        $data = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|min:8',
            'confirm_password' => 'required|same:password',
        ]);
        return $this->renderResponse('register-customer', [
            'pageTitle' => 'Customer Registration',
            'message' => '',
        ]);
    }
    public function registerVendor(Request $request): Response
    {
        $data = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|min:8',
            'confirm_password' => 'required|same:password',
        ]);
        return $this->renderResponse('register-vendor', [
            'pageTitle' => 'Vendor Registration',
            'message' => '',
        ]);
    }

    /**
     * Handle the registration process.
     */
    public function register(Request $request): Response
    {
        $data = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|min:8',
            'confirm_password' => 'required|same:password',
        ]);

        if ($this->authService->isEmailRegistered($data['email'])) {
            return $this->response->withStatus(400)->withBody('Email is already in use.');
        }

        try {
            // Need to add scopes such as admin, customer, vendor etc.
            //Possibly add a form to select the scopes
            //Example Register User form 
            //Or Register Admin Form
            //Or Register Customer Form
            //Or Register Vendor Form and so on....

            $this->authService->registerUser($data['name'], $data['email'], $data['password']);
            return Response::redirect('/auth/login');
        } catch (Exception $e) {
            return $this->handleException($e, 'An error occurred during registration.');
        }
    }

    /**
     * Handle user login.
     */
    public function login(Request $request): Response
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'grant_type' => 'nullable|string|in:password,session',
        ]);

        try {
            if ($data['grant_type'] === 'password') {
                $result = $this->authService->loginWithPasswordGrant($data['email'], $data['password']);
                return $this->response
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody(json_encode($result));
            }

            $this->authService->loginSession($data['email'], $data['password']);
            return Response::redirect('/dashboard');
        } catch (Exception $e) {
            $errors = [$e->getMessage()];
            // Convert errors array to query string for redirect
            $error = http_build_query(['error' => $errors]);
            return Response::redirect('/auth/login?'.$error);
        }
    }

    /**
     * Display the login form.
     */
    public function showLoginForm(Request $request): Response
    {
        if($this->authService->isLoggedIn()){
            $this->redirect('/dashboard');
        }
        $errors = $request->query('error');
        $this->view->title = "User Login";

        // Generate CSRF token
        $csrfToken = $this->csrfService->getToken();

        return $this->renderResponse('showLogin', [
            'pageTitle' => 'User Login',
            'title' => "Login",
            'message' => 'Please log in to access your account.',
            'csrf_token' => $csrfToken,  // Add this
            'errors' => $errors,
        ]);
       
    }

    /**
     * Handle the logout process.
     */
    public function logout(): Response
    {
        $this->authService->logoutSession();
        return Response::redirect('/auth/login');
    }

    /**
     * Register a new client by the admin.
     */
    public function registerClient(Request $request): Response
    {
        $data = $request->validate([
            'name' => 'required|string',
            'scopes' => 'required|array',
            'admin_id' => 'required|int',
            'redirect_uri' => 'nullable|url',
        ]);

        if (!$this->authService->isAdmin()) {
            return $this->response
                ->withStatus(403)
                ->withHeader('Content-Type', 'application/json')
                ->withBody(json_encode(['error' => 'Unauthorized']));
        }

        try {
            $result = $this->authService->registerClient(
                $data['name'],
                $data['scopes'],
                $data['admin_id'],
                $data['redirect_uri']
            );

            return $this->response
                ->withStatus(201)
                ->withHeader('Content-Type', 'application/json')
                ->withBody(json_encode($result));
        } catch (Exception $e) {
            return $this->handleException($e, 'An error occurred while registering the client.');
        }
    }

    /**
     * Get a client token using client credentials or refresh token.
     */
    public function getClientToken(Request $request): Response
    {
        $data = $request->all();

        try {
            if (isset($data['refresh_token'])) {
                $result = $this->authService->refreshToken($data['refresh_token']);
            } elseif (isset($data['client_id'], $data['client_secret'])) {
                $result = $this->authService->getClientToken(
                    $data['client_id'],
                    $data['client_secret'],
                    $data['scopes'] ?? []
                );
            } else {
                return $this->response->withStatus(400)
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody(json_encode(['error' => 'Invalid input']));
            }

            return $this->response
                ->withStatus(200)
                ->withHeader('Content-Type', 'application/json')
                ->withBody(json_encode($result));
        } catch (Exception $e) {
            return $this->handleException($e, 'Failed to retrieve client token.');
        }
    }

    /**
     * Redirect the user to the OAuth provider's authorization page.
     */
    public function redirectToProvider(): Response
    {
        $authorizationUrl = $this->authService->getAuthorizationUrl();
        return $this->response
            ->withStatus(302)
            ->withHeader('Location', $authorizationUrl)
            ->withBody('Redirecting to provider.');
    }

    /**
     * Handle the OAuth callback and authenticate the user.
     */
    public function handleProviderCallback(Request $request): Response
    {
        try {
            $authorizationCode = $request->query('code');

            if (!$authorizationCode) {
                return $this->response->withStatus(400)->withBody('Authorization code not provided.');
            }

            $accessToken = $this->authService->getAccessToken($authorizationCode);
            $userData = $this->authService->authenticate($accessToken);

            $user = $this->userRepository->findByEmail($userData['email']);
            if (!$user) {
                $this->userRepository->create([
                    'name' => $userData['name'] ?? 'Unknown',
                    'email' => $userData['email'],
                    'password' => bin2hex(random_bytes(8)),
                ]);
            }

            return $this->response
                ->withStatus(200)
                ->withHeader('Content-Type', 'application/json')
                ->withBody(json_encode(['message' => 'User authenticated successfully.']));
        } catch (Exception $e) {
            return $this->handleException($e, 'Authentication failed.');
        }
    }


}
