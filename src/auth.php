<?php
/**
 * Authentication Helper Functions
 * Mulai session dan cek autentikasi
 */

// Mulai session jika belum
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Cek apakah user sudah login
 */
function isAuthenticated()
{
    return !empty($_SESSION['user']) && (
        !empty($_SESSION['user']['id']) || 
        (!empty($_SESSION['user']['is_scanner']) && $_SESSION['user']['is_scanner'] === true)
    );
}

/**
 * Dapatkan user yang sedang login
 */
function getAuthUser()
{
    return $_SESSION['user'] ?? null;
}

/**
 * Redirect ke login jika belum autentikasi
 * Safe header redirect
 */
function requireAuth()
{
    if (!isAuthenticated()) {
        // Redirect ke halaman login admin
        $loginUrl = '/perpustakaan-online/admin-login.php';
        header('Location: ' . $loginUrl, true, 302);
        exit;
    }
}

/**
 * Logout user
 */
function logout()
{
    session_destroy();
    // Redirect ke halaman index/home
    header('Location: /perpustakaan-online/index.php', true, 302);
    exit;
}

/**
 * Get the current user ID from session
 */
function getCurrentUserId()
{
    return $_SESSION['user']['id'] ?? null;
}

/**
 * Get the current school ID from session
 */
function getCurrentSchoolId()
{
    return $_SESSION['user']['school_id'] ?? null;
}

/**
 * Login user using a Scan Access Key (Mobile Scanner)
 * Creates a limited session for scanning
 */
function loginByScanKey($key)
{
    if (empty($key)) return false;

    // We need $pdo here. Since this might be called early, we'll require it if not globalized.
    $pdo = require __DIR__ . '/db.php';

    $stmt = $pdo->prepare('SELECT id, name FROM schools WHERE scan_access_key = :key');
    $stmt->execute(['key' => $key]);
    $school = $stmt->fetch();

    if ($school) {
        // Create a special session
        $_SESSION['user'] = [
            'id' => 0, // No specific user ID
            'name' => 'Mobile Scanner',
            'email' => 'scanner@' . $school['id'],
            'role' => 'librarian', // Grant librarian privileges for scanning
            'school_id' => $school['id'],
            'is_scanner' => true // Flag for scanner session
        ];
        return true;
    }

    return false;
}
?>