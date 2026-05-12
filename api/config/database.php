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
                $socket = null;
            } else {
                // Priority 2: Individual env variables
                $host   = self::getEnv('MYSQLHOST', '127.0.0.1');
                $port   = self::getEnv('MYSQLPORT', '3306');
                $user   = self::getEnv('MYSQLUSER', 'root');
                $pass   = self::getEnv('MYSQLPASSWORD', '');
                $db     = self::getEnv('MYSQLDATABASE', 'campus_recruitment');
                $socket = self::getEnv('MYSQL_SOCKET', '');
            }

            // Build DSN - prefer Unix socket if available (local dev on Replit)
            if (!empty($socket) && file_exists($socket)) {
                $dsn = sprintf('mysql:unix_socket=%s;dbname=%s;charset=utf8mb4', $socket, $db);
            } else {
                $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $db);
            }

            try {
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ];

                // TiDB Cloud / Secure Cloud SSL Support
                $needsSsl = strpos($host, 'tidbcloud.com') !== false 
                         || strpos($host, 'aiven.io') !== false
                         || self::getEnv('MYSQL_SSL') === 'true';
                
                if ($needsSsl) {
                    // Always disable server cert verification so connection works
                    // even when no CA bundle is present on the Railway container
                    $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;

                    // Try to provide a CA file for better security (optional)
                    $caPaths = [
                        '/etc/ssl/certs/ca-certificates.crt',
                        '/etc/pki/tls/certs/ca-bundle.crt',
                        '/etc/ssl/ca-bundle.pem',
                        '/etc/ssl/cert.pem',
                    ];
                    foreach ($caPaths as $path) {
                        if (file_exists($path)) {
                            $options[PDO::MYSQL_ATTR_SSL_CA] = $path;
                            break;
                        }
                    }

                    // Append ssl_mode=REQUIRED to DSN so the driver enables SSL
                    // even when no CA file was found above
                    if (strpos($dsn, 'ssl_mode') === false) {
                        $dsn .= ';ssl_mode=REQUIRED';
                    }
                }

                self::$instance = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                $debug = defined('APP_DEBUG') ? APP_DEBUG : (self::getEnv('APP_DEBUG') === 'true' || self::getEnv('APP_DEBUG') === '1');
                
                if ($debug) {
                    throw new RuntimeException('Database connection failed: ' . $e->getMessage() . " (DSN: $dsn, User: $user)");
                }
                throw new RuntimeException('Database connection failed.');
            }
        }
        return self::$instance;
    }
}
