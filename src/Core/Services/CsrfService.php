<?php

declare(strict_types=1);

namespace App\Core\Services;

class CsrfService
{
    private const TOKEN_LENGTH = 32;
    private const TOKEN_NAME = 'csrf_token';
    private const SESSION_KEY = 'csrf_tokens';

    /**
     * Generate a new CSRF token
     */
    public function generateToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $token = bin2hex(random_bytes(self::TOKEN_LENGTH));
        
        // Store token in session with timestamp
        if (!isset($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = [];
        }
        
        $_SESSION[self::SESSION_KEY][$token] = [
            'created_at' => time(),
            'used' => false
        ];

        return $token;
    }

    /**
     * Validate a CSRF token
     */
    public function validateToken(string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION[self::SESSION_KEY][$token])) {
            return false;
        }

        $tokenData = $_SESSION[self::SESSION_KEY][$token];
        
        // Check if token is already used (one-time use)
        if ($tokenData['used']) {
            return false;
        }

        // Check if token is expired (24 hours)
        if (time() - $tokenData['created_at'] > 86400) {
            unset($_SESSION[self::SESSION_KEY][$token]);
            return false;
        }

        // Mark token as used
        $_SESSION[self::SESSION_KEY][$token]['used'] = true;
        
        return true;
    }

    /**
     * Get current token or generate new one
     */
    public function getToken(): string
    {
        return $this->generateToken();
    }

    /**
     * Get the token name for form fields
     */
    public function getTokenName(): string
    {
        return self::TOKEN_NAME;
    }

    /**
     * Clean up expired tokens
     */
    public function cleanupExpiredTokens(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION[self::SESSION_KEY])) {
            return;
        }

        $currentTime = time();
        foreach ($_SESSION[self::SESSION_KEY] as $token => $data) {
            if ($currentTime - $data['created_at'] > 86400) {
                unset($_SESSION[self::SESSION_KEY][$token]);
            }
        }
    }
}