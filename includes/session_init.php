<?php
/**
 * Session Initialization for Silah Project
 * 
 * Optimized for Vercel's Read-Only Filesystem and Serverless architecture.
 * We use a custom Database Session Handler to ensure 100% persistence
 * across different serverless invocations (which /tmp does not provide).
 */

class SilahSessionHandler implements SessionHandlerInterface {
    private $pdo;

    public function __construct() {
        // We'll lazy-load the PDO connection
    }

    private function getPdo() {
        if ($this->pdo === null) {
            // Include db_connect if not already there
            require_once __DIR__ . '/db_connect.php';
            global $pdo;
            $this->pdo = $pdo;
        }
        return $this->pdo;
    }

    public function open($path, $name): bool {
        return true;
    }

    public function close(): bool {
        return true;
    }

    public function read($id): string {
        $pdo = $this->getPdo();
        if (!$pdo) return '';
        
        try {
            $stmt = $pdo->prepare("SELECT data FROM sessions WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? (string)$row['data'] : '';
        } catch (Exception $e) {
            return '';
        }
    }

    public function write($id, $data): bool {
        $pdo = $this->getPdo();
        if (!$pdo) return false;

        try {
            $stmt = $pdo->prepare("REPLACE INTO sessions (id, data, timestamp) VALUES (?, ?, ?)");
            return $stmt->execute([$id, $data, time()]);
        } catch (Exception $e) {
            return false;
        }
    }

    public function destroy($id): bool {
        $pdo = $this->getPdo();
        if (!$pdo) return false;

        try {
            $stmt = $pdo->prepare("DELETE FROM sessions WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            return false;
        }
    }

    public function gc($max_lifetime): int|false {
        $pdo = $this->getPdo();
        if (!$pdo) return false;

        try {
            $stmt = $pdo->prepare("DELETE FROM sessions WHERE timestamp < ?");
            $stmt->execute([time() - $max_lifetime]);
            return $stmt->rowCount();
        } catch (Exception $e) {
            return false;
        }
    }
}

// Only use DB sessions if we're on Vercel/Serverless
if (getenv('VERCEL') === '1' || getenv('AWS_LAMBDA_FUNCTION_NAME')) {
    // Check if we can use DB sessions
    require_once __DIR__ . '/db_connect.php';
    if ($pdo) {
        $handler = new SilahSessionHandler();
        session_set_save_handler($handler, true);
    } else {
        // Fallback to /tmp if DB is not available
        session_save_path('/tmp');
    }
} else {
    // Local development (WAMP/XAMPP)
    $session_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'tmp';
    if (!is_dir($session_path)) {
        @mkdir($session_path, 0777, true);
    }
    if (is_writable($session_path)) {
        session_save_path($session_path);
    }
}

// Ensure session starts correctly
if (session_status() === PHP_SESSION_NONE) {
    if (getenv('VERCEL') === '1' || getenv('AWS_LAMBDA_FUNCTION_NAME')) {
        ini_set('session.cookie_secure', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.gc_maxlifetime', '86400');
        ini_set('session.cookie_lifetime', '86400');
        ini_set('session.cookie_path', '/');
        ini_set('session.cookie_samesite', 'Lax');
    }
    
    session_start();
}
?>
