<?php

declare(strict_types=1);

namespace App\Controllers\User;

use App\Core\Controller;
use App\Core\Response;
use App\Interfaces\RequestInterface;
use App\Models\User;
use App\Services\UserValidationService;
use App\Services\SessionService;
use App\Services\SecurityLoggerService;
use App\Security\CSRFToken;
use App\Middleware\RateLimitMiddleware;

/**
 * Authentication Controller
 * 
 * Handles user login, logout, and authentication-related functionality.
 * PHP 8.4 compatible with strict typing and comprehensive security measures.
 */
class AuthController extends Controller
{
    private UserValidationService $validationService;
    private SessionService $sessionService;
    private SecurityLoggerService $securityLogger;
    private CSRFToken $csrfToken;
    private RateLimitMiddleware $rateLimitMiddleware;

    public function __construct(
        UserValidationService $validationService,
        SessionService $sessionService,
        SecurityLoggerService $securityLogger,
        CSRFToken $csrfToken,
        RateLimitMiddleware $rateLimitMiddleware
    ) {
        $this->validationService = $validationService;
        $this->sessionService = $sessionService;
        $this->securityLogger = $securityLogger;
        $this->csrfToken = $csrfToken;
        $this->rateLimitMiddleware = $rateLimitMiddleware;
    }

    /**
     * Display login form.
     *
     * @param RequestInterface $request Request object
     * @return array<string, mixed> View data
     */
    public function showLogin(RequestInterface $request): array
    {
        // Redirect if already authenticated
        if ($this->sessionService->isAuthenticated()) {
            return [
                'view' => 'auth/login',
                'data' => [
                    'redirect' => '/dashboard',
                    'message' => 'You are already logged in.',
                    'includeLayout' => false
                ]
            ];
        }

        // Check for remember me cookie
        $rememberedUser = $this->sessionService->checkRememberMe(
            $request->getClientIp(),
            $request->getUserAgent()
        );

        if ($rememberedUser) {
            // Auto-login from remember me
            $this->sessionService->startUserSession(
                $rememberedUser,
                true,
                $request->getClientIp(),
                $request->getUserAgent()
            );

            return [
                'view' => 'auth/login',
                'data' => [
                    'redirect' => '/dashboard',
                    'message' => 'Welcome back! You have been automatically logged in.',
                    'includeLayout' => false
                ]
            ];
        }

        return [
            'view' => 'auth/login',
            'data' => [
                'csrf_token' => $this->csrfToken->generate(),
                'title' => 'Login',
                'errors' => [],
                'old_input' => []
            ]
        ];
    }

    /**
     * Handle login form submission.
     *
     * @param RequestInterface $request Request object
     * @return array<string, mixed> View data or redirect
     */
    public function login(RequestInterface $request): array
    {
        $ipAddress = $request->getClientIp();
        $userAgent = $request->getUserAgent();
        $loginData = $request->getBody();

        // Validate CSRF token
        if (!$this->csrfToken->verify($loginData['csrf_token'] ?? '')) {
            $this->securityLogger->logAuthFailure(
                'session',
                $loginData['email'] ?? 'unknown',
                'csrf_token_invalid',
                $ipAddress,
                $userAgent
            );

            return [
                'view' => 'auth/login',
                'data' => [
                    'csrf_token' => $this->csrfToken->generate(),
                    'errors' => ['csrf_token' => 'Invalid security token. Please try again.'],
                    'old_input' => $this->sanitizeInput($loginData),
                    'title' => 'Login'
                ]
            ];
        }

        // Sanitize input
        $loginData = $this->validationService->sanitizeUserInput($loginData);

        // Validate input
        $validation = $this->validationService->validateLogin($loginData);
        if (!$validation->isValid()) {
            return [
                'view' => 'auth/login',
                'data' => [
                    'csrf_token' => $this->csrfToken->generate(),
                    'errors' => $validation->getErrors(),
                    'old_input' => $this->sanitizeInput($loginData),
                    'title' => 'Login'
                ]
            ];
        }

        // Attempt to find user by email or username
        $user = $this->findUserByEmailOrUsername($loginData['email']);
        
        if (!$user) {
            $this->securityLogger->logAuthFailure(
                'session',
                $loginData['email'],
                'user_not_found',
                $ipAddress,
                $userAgent
            );

            // Record failed attempt for rate limiting
            $this->rateLimitMiddleware->recordFailedAttempt($request);

            return [
                'view' => 'auth/login',
                'data' => [
                    'csrf_token' => $this->csrfToken->generate(),
                    'errors' => ['email' => 'Invalid credentials. Please check your email and password.'],
                    'old_input' => $this->sanitizeInput($loginData),
                    'title' => 'Login'
                ]
            ];
        }

        // Check if user account is active (not soft deleted)
        if ($user->isDeleted()) {
            $this->securityLogger->logAuthFailure(
                'session',
                $loginData['email'],
                'account_disabled',
                $ipAddress,
                $userAgent
            );

            // Record failed attempt for rate limiting
            $this->rateLimitMiddleware->recordFailedAttempt($request);

            return [
                'view' => 'auth/login',
                'data' => [
                    'csrf_token' => $this->csrfToken->generate(),
                    'errors' => ['email' => 'This account has been disabled. Please contact support.'],
                    'old_input' => $this->sanitizeInput($loginData),
                    'title' => 'Login'
                ]
            ];
        }

        // Verify password
        if (!$user->verifyPassword($loginData['password'])) {
            $this->securityLogger->logAuthFailure(
                'session',
                $loginData['email'],
                'invalid_password',
                $ipAddress,
                $userAgent
            );

            // Record failed attempt for rate limiting
            $this->rateLimitMiddleware->recordFailedAttempt($request);

            return [
                'view' => 'auth/login',
                'data' => [
                    'csrf_token' => $this->csrfToken->generate(),
                    'errors' => ['password' => 'Invalid credentials. Please check your email and password.'],
                    'old_input' => $this->sanitizeInput($loginData),
                    'title' => 'Login'
                ]
            ];
        }

        // Successful authentication - start session
        $rememberMe = isset($loginData['remember_me']) && $loginData['remember_me'] === '1';
        
        if (!$this->sessionService->startUserSession($user, $rememberMe, $ipAddress, $userAgent)) {
            return [
                'view' => 'auth/login',
                'data' => [
                    'csrf_token' => $this->csrfToken->generate(),
                    'errors' => ['general' => 'Unable to start session. Please try again.'],
                    'old_input' => $this->sanitizeInput($loginData),
                    'title' => 'Login'
                ]
            ];
        }

        // Redirect to intended destination or dashboard
        $redirectUrl = $loginData['redirect'] ?? '/dashboard';
        
        return [
            'view' => 'auth/login',
            'data' => [
                'redirect' => $redirectUrl,
                'message' => 'Login successful! Redirecting...',
                'includeLayout' => false
            ]
        ];
    }

    /**
     * Handle user logout.
     *
     * @param RequestInterface $request Request object
     * @return array<string, mixed> View data
     */
    public function logout(RequestInterface $request): array
    {
        $this->sessionService->destroySession('user_logout');

        return [
            'view' => 'auth/login',
            'data' => [
                'message' => 'You have been successfully logged out.',
                'csrf_token' => $this->csrfToken->generate(),
                'title' => 'Login',
                'errors' => [],
                'old_input' => []
            ]
        ];
    }

    /**
     * Display user dashboard (protected route).
     *
     * @param RequestInterface $request Request object
     * @return array<string, mixed> View data
     */
    public function dashboard(RequestInterface $request): array
    {
        $user = $this->sessionService->getCurrentUser();
        
        if (!$user) {
            return [
                'view' => 'auth/login',
                'data' => [
                    'redirect' => '/login',
                    'message' => 'Please log in to access your dashboard.',
                    'includeLayout' => false
                ]
            ];
        }

        $sessionInfo = $this->sessionService->getSessionInfo();

        return [
            'view' => 'auth/dashboard',
            'data' => [
                'user' => $user,
                'session_info' => $sessionInfo,
                'title' => 'Dashboard - Welcome ' . $user->getAttribute('first_name'),
                'full_name' => $user->getFullName()
            ]
        ];
    }

    /**
     * Handle session check (AJAX endpoint).
     *
     * @param RequestInterface $request Request object
     * @return Response JSON response
     */
    public function checkSession(RequestInterface $request): Response
    {
        $response = new Response();
        
        $isValid = $this->sessionService->validateSession(
            $request->getClientIp(),
            $request->getUserAgent()
        );

        $data = [
            'authenticated' => $isValid,
            'session_info' => $isValid ? $this->sessionService->getSessionInfo() : null
        ];

        return $response->json($data);
    }

    /**
     * Find user by email or username.
     *
     * @param string $identifier Email or username
     * @return User|null User if found, null otherwise
     */
    private function findUserByEmailOrUsername(string $identifier): ?User
    {
        // Try to find by email first
        $users = User::findBy(['email' => $identifier]);
        if (!empty($users)) {
            return $users[0];
        }

        // Try to find by username
        $users = User::findBy(['username' => $identifier]);
        if (!empty($users)) {
            return $users[0];
        }

        return null;
    }

    /**
     * Sanitize input data for display (remove passwords).
     *
     * @param array<string, mixed> $input Input data
     * @return array<string, mixed> Sanitized data
     */
    private function sanitizeInput(array $input): array
    {
        $sanitized = $input;
        
        // Remove sensitive fields
        unset($sanitized['password']);
        unset($sanitized['password_confirmation']);
        unset($sanitized['csrf_token']);
        
        return $sanitized;
    }
}