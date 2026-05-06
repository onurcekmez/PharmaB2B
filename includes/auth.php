<?php
/**
 * Authentication Guard
 * 
 * Include this file at the top of every protected page.
 * It starts the session, checks if the user is logged in,
 * and optionally checks the user's role.
 * 
 * Usage:
 *   require_once 'includes/auth.php';
 *   authorize();                     // Any logged-in user
 *   authorize('pharmacy');           // Only pharmacy role
 *   authorize(['warehouse','admin']); // Multiple roles allowed
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in and has the required role.
 * Redirects to login.php if not authorized.
 *
 * @param string|array|null $required_role  Role(s) allowed to access the page
 */
function authorize($required_role = null) {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }

    // Check role if specified
    if ($required_role !== null) {
        $roles = is_array($required_role) ? $required_role : [$required_role];
        if (!in_array($_SESSION['role'], $roles)) {
            // User doesn't have the required role
            header('Location: dashboard.php');
            exit;
        }
    }
}

/**
 * Check if the current user is logged in.
 * @return bool
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Get the current user's role.
 * @return string|null
 */
function get_role() {
    return $_SESSION['role'] ?? null;
}
