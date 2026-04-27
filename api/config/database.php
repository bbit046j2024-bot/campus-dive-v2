<?php
/**
 * Database Connection (PDO Singleton)
 */
class Database {
    private static ?PDO $instance = null;

    private static function getEnv(string $key, $default = null) {
        $val = getenv($key);
        if ($val !== false && $val !== '') return $val;
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') return $_ENV[$key];
        if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') return $_SERVER[$key];
        return $default;
    }

    public static function getInstance(): PDO {
        if (self::$instance === null) {
            // Priority 1: MYSQL_URL (The most reliable on Railway)
            $mysqlUrl = self::getEnv('MYSQL_URL');
            
            if ($mysqlUrl) {
                // Format: mysql://user:pass@host:port/db
                $parts = parse_url($mysqlUrl);
                $host = $parts['host'] ?? '127.0.0.1';
                $port = $parts['port'] ?? '3306';
                $user = $parts['user'] ?? 'root';
                $pass = $parts['pass'] ?? '';
                $db   = ltrim($parts['path'] ?? 'railway', '/');
            } else {
                // Priority 2: Individual Railway variables
                $host = self::getEnv('MYSQLHOST', '127.0.0.1');
                $port = self::getEnv('MYSQLPORT', '3306');
                $user = self::getEnv('MYSQLUSER', 'root');
                $pass = self::getEnv('MYSQLPASSWORD', '');
                $db   = self::getEnv('MYSQLDATABASE', 'railway');
            }

            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $db);

            try {
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ];

                // TiDB Cloud / Secure Cloud Support
                if (strpos($host, 'tidbcloud.com') !== false || self::getEnv('MYSQL_SSL') === 'true') {
                    $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false; // For compatibility
                    // If you have a specific CA cert, you would add it here
                }

                self::$instance = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                // Use the constant if defined, otherwise check env
                $debug = defined('APP_DEBUG') ? APP_DEBUG : (self::getEnv('APP_DEBUG') === 'true' || self::getEnv('APP_DEBUG') === '1');
                
                if ($debug) {
                    throw new RuntimeException('Database connection failed: ' . $e->getMessage() . " (Host: $host, Port: $port, DB: $db, User: $user)");
                }
                throw new RuntimeException('Database connection failed.');
            }
        }
        return self::$instance;
    }
}
