<?php

declare(strict_types=1);

namespace App\Controllers\User;

use App\Core\Controller;
use App\Interfaces\RequestInterface;
use App\Interfaces\ResponseInterface;
use App\Models\User;
use App\Services\UserValidationService;
use App\Services\SessionService;
use App\Services\SecurityLoggerService;
use App\Security\CSRFToken;

/**
 * Registration Controller
 * 
 * Handles user registration and account creation functionality.
 * PHP 8.4 compatible with strict typing and comprehensive security measures.
 */
class RegisterController extends Controller
{
    private UserValidationService $validationService;
    private SessionService $sessionService;
    private SecurityLoggerService $securityLogger;
    private CSRFToken $csrfToken;
    private ResponseInterface $response;

    public function __construct(
        UserValidationService $validationService,
        SessionService $sessionService,
        SecurityLoggerService $securityLogger,
        CSRFToken $csrfToken,
        ResponseInterface $response
    ) {
        $this->validationService = $validationService;
        $this->sessionService = $sessionService;
        $this->securityLogger = $securityLogger;
        $this->csrfToken = $csrfToken;
        $this->response = $response;
    }

    /**
     * Display registration form.
     *
     * @param RequestInterface $request Request object
     * @return array<string, mixed> View data
     */
    public function show(RequestInterface $request): array
    {
        // Redirect if already authenticated
        if ($this->sessionService->isAuthenticated()) {
            return [
                'view' => 'auth/register',
                'data' => [
                    'redirect' => '/dashboard',
                    'message' => 'You are already logged in.',
                    'includeLayout' => false
                ]
            ];
        }

        return [
            'view' => 'auth/register',
            'data' => [
                'csrf_token' => $this->csrfToken->generate(),
                'title' => 'Create Account',
                'errors' => [],
                'old_input' => [],
                'data' => [
                    'password_requirements' => $this->getPasswordRequirements(),
                    'errors' => [],
                    'old_input' => []
                ]
            ]
        ];
    }

    /**
     * Handle registration form submission.
     *
     * @param RequestInterface $request Request object
     * @return array<string, mixed> View data or redirect
     */
    public function register(RequestInterface $request): array
    {
        $ipAddress = $request->getClientIp();
        $userAgent = $request->getUserAgent();
        $registrationData = $request->getBody();

        // Validate CSRF token
        if (!$this->csrfToken->verify($registrationData['csrf_token'] ?? '')) {
            $this->securityLogger->logAuthFailure(
                'registration',
                $registrationData['email'] ?? 'unknown',
                'csrf_token_invalid',
                $ipAddress,
                $userAgent
            );

            return [
                'view' => 'auth/register',
                'data' => [
                    'csrf_token' => $this->csrfToken->generate(),
                    'errors' => ['csrf_token' => 'Invalid security token. Please try again.'],
                    'old_input' => $this->sanitizeInput($registrationData),
                    'title' => 'Create Account',
                    'data' => [
                        'password_requirements' => $this->getPasswordRequirements(),
                        'errors' => ['csrf_token' => 'Invalid security token. Please try again.'],
                        'old_input' => $this->sanitizeInput($registrationData)
                    ]
                ]
            ];
        }

        // Sanitize input
        $registrationData = $this->validationService->sanitizeUserInput($registrationData);

        // Validate input
        $validation = $this->validationService->validateRegistration($registrationData);
        if (!$validation->isValid()) {
            return [
                'view' => 'auth/register',
                'data' => [
                    'csrf_token' => $this->csrfToken->generate(),
                    'errors' => $validation->getErrors(),
                    'old_input' => $this->sanitizeInput($registrationData),
                    'title' => 'Create Account',
                    'data' => [
                        'password_requirements' => $this->getPasswordRequirements(),
                        'errors' => $validation->getErrors(),
                        'old_input' => $this->sanitizeInput($registrationData)
                    ]
                ]
            ];
        }

        // Check if username already exists
        if ($this->isUsernameExists($registrationData['username'])) {
            return [
                'view' => 'auth/register',
                'data' => [
                    'csrf_token' => $this->csrfToken->generate(),
                    'errors' => ['username' => 'This username is already taken. Please choose another.'],
                    'old_input' => $this->sanitizeInput($registrationData),
                    'title' => 'Create Account',
                    'data' => [
                        'password_requirements' => $this->getPasswordRequirements(),
                        'errors' => ['username' => 'This username is already taken. Please choose another.'],
                        'old_input' => $this->sanitizeInput($registrationData)
                    ]
                ]
            ];
        }

        // Check if email already exists
        if ($this->isEmailExists($registrationData['email'])) {
            return [
                'view' => 'auth/register',
                'data' => [
                    'csrf_token' => $this->csrfToken->generate(),
                    'errors' => ['email' => 'An account with this email address already exists.'],
                    'old_input' => $this->sanitizeInput($registrationData),
                    'title' => 'Create Account',
                    'data' => [
                        'password_requirements' => $this->getPasswordRequirements(),
                        'errors' => ['email' => 'An account with this email address already exists.'],
                        'old_input' => $this->sanitizeInput($registrationData)
                    ]
                ]
            ];
        }

        // Prepare user data for creation
        $userData = [
            'username' => $registrationData['username'],
            'email' => $registrationData['email'],
            'password' => $registrationData['password'],
            'first_name' => $registrationData['first_name'],
            'last_name' => $registrationData['last_name']
        ];

        try {
            // Create user account
            $user = User::createUser($userData);

            // Log successful registration
            $this->securityLogger->logAuthSuccess(
                $user->getId(),
                'registration',
                $ipAddress,
                $userAgent
            );

            // Auto-login the newly registered user
            $this->sessionService->startUserSession($user, false, $ipAddress, $userAgent);

            // Return success response
            return [
                'view' => 'auth/register',
                'data' => [
                    'redirect' => '/dashboard',
                    'message' => 'Account created successfully! Welcome to our platform.',
                    'includeLayout' => false
                ]
            ];

        } catch (\Exception $e) {
            // Log registration failure
            $this->securityLogger->logAuthFailure(
                'registration',
                $registrationData['email'],
                'account_creation_failed',
                $ipAddress,
                $userAgent
            );

            return [
                'view' => 'auth/register',
                'data' => [
                    'csrf_token' => $this->csrfToken->generate(),
                    'errors' => ['general' => 'Unable to create account. Please try again later.'],
                    'old_input' => $this->sanitizeInput($registrationData),
                    'title' => 'Create Account',
                    'data' => [
                        'password_requirements' => $this->getPasswordRequirements(),
                        'errors' => ['general' => 'Unable to create account. Please try again later.'],
                        'old_input' => $this->sanitizeInput($registrationData)
                    ]
                ]
            ];
        }
    }

    /**
     * Check username availability (AJAX endpoint).
     *
     * @param RequestInterface $request Request object
     * @return ResponseInterface JSON response
     */
    public function checkUsername(RequestInterface $request): ResponseInterface
    {
        $username = $request->getQueryParam('username', '');
        
        if (empty($username)) {
            return $this->response->json([
                'available' => false,
                'message' => 'Username is required'
            ])->setStatusCode(400);
        }

        // Validate username format
        if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
            return $this->response->json([
                'available' => false,
                'message' => 'Username must be 3-30 characters, alphanumeric and underscores only'
            ])->setStatusCode(400);
        }

        $exists = $this->isUsernameExists($username);
        
        return $this->response->json([
            'available' => !$exists,
            'message' => $exists ? 'Username is already taken' : 'Username is available'
        ]);
    }

    /**
     * Check email availability (AJAX endpoint).
     *
     * @param RequestInterface $request Request object
     * @return ResponseInterface JSON response
     */
    public function checkEmail(RequestInterface $request): ResponseInterface
    {
        $email = $request->getQueryParam('email', '');
        
        if (empty($email)) {
            return $this->response->json([
                'available' => false,
                'message' => 'Email is required'
            ])->setStatusCode(400);
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->response->json([
                'available' => false,
                'message' => 'Please provide a valid email address'
            ])->setStatusCode(400);
        }

        $exists = $this->isEmailExists($email);
        
        return $this->response->json([
            'available' => !$exists,
            'message' => $exists ? 'Email is already registered' : 'Email is available'
        ]);
    }

    /**
     * Get password strength (AJAX endpoint).
     *
     * @param RequestInterface $request Request object
     * @return ResponseInterface JSON response
     */
    public function checkPasswordStrength(RequestInterface $request): ResponseInterface
    {
        $password = $request->getBodyParam('password', '');
        
        if (empty($password)) {
            return $this->response->json([
                'score' => 0,
                'strength' => 'No Password',
                'feedback' => ['Please enter a password'],
                'is_valid' => false
            ])->setStatusCode(400);
        }

        $strengthAnalysis = $this->validationService->getPasswordStrength($password);
        
        return $this->response->json($strengthAnalysis);
    }

    /**
     * Check if username already exists.
     *
     * @param string $username Username to check
     * @return bool True if username exists
     */
    private function isUsernameExists(string $username): bool
    {
        $users = User::findBy(['username' => $username]);
        return !empty($users);
    }

    /**
     * Check if email already exists.
     *
     * @param string $email Email to check
     * @return bool True if email exists
     */
    private function isEmailExists(string $email): bool
    {
        $users = User::findBy(['email' => $email]);
        return !empty($users);
    }

    /**
     * Get password requirements for display.
     *
     * @return array<string> Password requirements
     */
    private function getPasswordRequirements(): array
    {
        return [
            'At least 8 characters long',
            'At least one uppercase letter (A-Z)',
            'At least one lowercase letter (a-z)', 
            'At least one number (0-9)',
            'At least one special character (!@#$%^&*)'
        ];
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