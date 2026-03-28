<?php
/**
 * POLYGUARD AI - Security & Authentication Middleware
 * 
 * Features:
 * - CSRF Token Protection
 * - API Token Validation
 * - Rate Limiting
 * - Input Validation & Sanitization
 * - SQL Injection Prevention (PDO Prepared Statements)
 * - XSS Prevention (HTMLSpecialChars)
 * - Security Logging
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Security Headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
// Content Security Policy - allow Google Maps and Fonts for dashboards
header("Content-Security-Policy: default-src 'self' https: data:; " .
    "script-src 'self' 'unsafe-inline' https://maps.googleapis.com https://maps.gstatic.com; " .
    "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
    "font-src 'self' https://fonts.gstatic.com data:; " .
    "img-src 'self' data: https:; " .
    "connect-src 'self' https://maps.googleapis.com https://maps.gstatic.com; " .
    "frame-src 'self' https://www.google.com https://maps.google.com https://maps.gstatic.com;");

class SecurityMiddleware {
    private static $rate_limit_file = __DIR__ . '/../.rate_limit';
    private static $max_attempts = 10;
    private static $time_window = 300; // 5 minutes

    /**
     * Generate CSRF Token
     */
    public static function generateCSRFToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Verify CSRF Token
     */
    public static function verifyCSRFToken($token) {
        if (empty($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
            throw new Exception('CSRF token validation failed');
        }
        return true;
    }

    /**
     * Generate API Key (for external integrations)
     */
    public static function generateAPIKey() {
        return hash('sha256', bin2hex(random_bytes(32)) . time());
    }

    /**
     * Validate API Request
     */
    public static function validateAPIRequest($api_key, $pdo) {
        $stmt = $pdo->prepare("SELECT * FROM api_keys WHERE api_key = ? AND is_active = 1");
        $stmt->execute([$api_key]);
        $key = $stmt->fetch();

        if (!$key) {
            http_response_code(401);
            throw new Exception('Unauthorized: Invalid API key');
        }

        // Update last used timestamp
        $pdo->prepare("UPDATE api_keys SET last_used = NOW() WHERE api_key_id = ?")
            ->execute([$key['api_key_id']]);

        return $key;
    }

    /**
     * Rate Limiting
     */
    public static function checkRateLimit($identifier) {
        $key = "rate_limit_" . hash('md5', $identifier);
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['attempts' => 0, 'first_attempt' => time()];
        }

        $current_time = time();
        $time_elapsed = $current_time - $_SESSION[$key]['first_attempt'];

        // Reset if time window has passed
        if ($time_elapsed > self::$time_window) {
            $_SESSION[$key] = ['attempts' => 0, 'first_attempt' => $current_time];
        }

        $_SESSION[$key]['attempts']++;

        if ($_SESSION[$key]['attempts'] > self::$max_attempts) {
            http_response_code(429);
            throw new Exception('Rate limit exceeded. Try again later.');
        }

        return true;
    }

    /**
     * Sanitize Input
     */
    public static function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeInput'], $input);
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Validate Email
     */
    public static function validateEmail($email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }
        return true;
    }

    /**
     * Hash Password (SHA256 + Salt)
     */
    public static function hashPassword($password) {
        $salt = bin2hex(random_bytes(16));
        $hash = hash('sha256', $password . $salt);
        return $salt . ':' . $hash;
    }

    /**
     * Verify Password
     */
    public static function verifyPassword($password, $stored_hash) {
        list($salt, $hash) = explode(':', $stored_hash);
        $new_hash = hash('sha256', $password . $salt);
        return hash_equals($hash, $new_hash);
    }

    /**
     * Log Security Events
     */
    public static function logSecurityEvent($event_type, $user_id, $details, $pdo) {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            
            $stmt = $pdo->prepare("INSERT INTO security_logs (event_type, user_id, ip_address, user_agent, details) 
                                 VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$event_type, $user_id, $ip, $user_agent, json_encode($details)]);
        } catch (Exception $e) {
            // Silently fail to avoid breaking functionality
        }
    }

    /**
     * Encrypt Data
     */
    public static function encryptData($data, $encryption_key) {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('AES-256-CBC'));
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $encryption_key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt Data
     */
    public static function decryptData($encrypted_data, $encryption_key) {
        $data = base64_decode($encrypted_data);
        $iv_length = openssl_cipher_iv_length('AES-256-CBC');
        $iv = substr($data, 0, $iv_length);
        $encrypted = substr($data, $iv_length);
        return openssl_decrypt($encrypted, 'AES-256-CBC', $encryption_key, 0, $iv);
    }
}

// Create security_logs table if not exists
function initSecurityTables($pdo) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS security_logs (
            log_id INT AUTO_INCREMENT PRIMARY KEY,
            event_type VARCHAR(50) NOT NULL,
            user_id INT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            details JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX(event_type, created_at),
            INDEX(user_id)
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS api_keys (
            api_key_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            api_key VARCHAR(255) UNIQUE NOT NULL,
            scope VARCHAR(100),
            is_active TINYINT DEFAULT 1,
            last_used TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        )");
    } catch (Exception $e) {
        // Tables might already exist
    }
}

?>
