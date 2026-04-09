<?php
/**
 * Session Initialization for Silah Project
 * 
 * Optimized for Vercel's Read-Only Filesystem.
 * On Vercel, we MUST use the system's /tmp directory for sessions.
 */

// If running on Vercel (AWS Lambda environment)
if (getenv('VERCEL') === '1' || getenv('AWS_LAMBDA_FUNCTION_NAME')) {
    // Vercel only allows writing to the system's /tmp directory
    $session_path = '/tmp';
} else {
    // Local development (WAMP/XAMPP)
    $session_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'tmp';
    
    if (!is_dir($session_path)) {
        @mkdir($session_path, 0777, true);
    }
}

// Only set session_save_path if it's writable
if (is_writable($session_path)) {
    session_save_path($session_path);
}

// Ensure session starts correctly
if (session_status() === PHP_SESSION_NONE) {
    // Set some secure session defaults for production
    if (getenv('VERCEL') === '1') {
        ini_set('session.cookie_secure', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.use_only_cookies', '1');
    }
    
    session_start();
}
?>
