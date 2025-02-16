<?php

namespace App\Traits;

trait ACLControl
{
    /**
     * Checks if the currently logged-in user has the required role.
     *
     * @param string|array $requiredRole A role or array of acceptable roles.
     * @return bool
     */
    public function checkPermission($requiredRole): bool
    {
        // Assuming you store the authenticated user information in session.
        if (!isset($_SESSION['user'])) {
            return false;
        }
        
        $userRole = $_SESSION['user']['role'] ?? null;
        if (is_array($requiredRole)) {
            return in_array($userRole, $requiredRole, true);
        }
        return $userRole === $requiredRole;
    }

    /**
     * Enforces that the current user has the required role.
     * If the role is not met, it sends a 403 Forbidden response.
     *
     * @param string|array $requiredRole
     * @return void
     */
    public function requirePermission($requiredRole): void
    {
        if (!$this->checkPermission($requiredRole)) {
            header('HTTP/1.1 403 Forbidden');
            exit('Forbidden: You do not have permission to access this page.');
        }
    }
}