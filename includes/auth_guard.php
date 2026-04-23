<?php
function requireLogin(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['user_id'])) {
        header('Location: /auth/login.php');
        exit;
    }
}

function requireRole(string ...$roles): void {
    requireLogin();
    if (!in_array($_SESSION['role'], $roles, true)) {
        http_response_code(403);
        die('<h2>403 — Access Denied</h2>');
    }
}

/*  Usage examples:
    requireLogin();                          // any logged-in user
    requireRole('Mangaka');                  // Mangaka only
    requireRole('Studio');                   // Studio only
    requireRole('Admin');                    // Admin only
    requireRole('Mangaka', 'Admin');         // multiple roles
*/
