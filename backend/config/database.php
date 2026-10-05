<?php
/**
 * Database Configuration & Connection (PDO)
 * Book Banko Admin Panel & API Backend
 * Supports Supabase PostgreSQL (Production) & MySQL (Local Dev)
 */

require_once __DIR__ . '/constants.php';

// MySQL fallback configuration defaults
define('MYSQL_HOST', getenv('MYSQL_DB_HOST') ?: 'localhost');
define('MYSQL_PORT', getenv('MYSQL_DB_PORT') ?: '3306');
define('MYSQL_NAME', getenv('MYSQL_DB_NAME') ?: 'book_banko_db');
define('MYSQL_USER', getenv('MYSQL_DB_USER') ?: 'root');
define('MYSQL_PASS', getenv('MYSQL_DB_PASS') ?: '');

class Database {
    private static ?PDO $instance = null;
    private static string $activeDriver = 'pgsql';

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $driver = strtolower(DB_DRIVER);
            self::$activeDriver = $driver;

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                if ($driver === 'pgsql') {
                    // Supabase PostgreSQL DSN
                    $host = SUPABASE_DB_HOST;
                    $port = SUPABASE_DB_PORT;
                    $dbname = SUPABASE_DB_NAME;
                    $user = SUPABASE_DB_USER;
                    $pass = SUPABASE_DB_PASSWORD;
                    $sslmode = SUPABASE_DB_SSLMODE;

                    if (empty($host) || strpos($host, 'your-project-ref') !== false) {
                        throw new Exception("Supabase connection details are not configured. Please set SUPABASE_DB_HOST, SUPABASE_DB_USER, and SUPABASE_DB_PASSWORD in backend/.env.");
                    }

                    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode={$sslmode}";
                    self::$instance = new PDO($dsn, $user, $pass, $options);
                } else {
                    // MySQL DSN
                    $dsn = "mysql:host=" . MYSQL_HOST . ";port=" . MYSQL_PORT . ";dbname=" . MYSQL_NAME . ";charset=utf8mb4";
                    self::$instance = new PDO($dsn, MYSQL_USER, MYSQL_PASS, $options);
                }
            } catch (Exception $e) {
                // If pgsql failed because credentials aren't set yet, try fallback to MySQL if available
                if ($driver === 'pgsql' && (!extension_loaded('pdo_pgsql') || strpos($e->getMessage(), 'not configured') !== false || strpos($e->getMessage(), 'refused') !== false)) {
                    if (extension_loaded('pdo_mysql')) {
                        try {
                            $mysqlDsn = "mysql:host=" . MYSQL_HOST . ";port=" . MYSQL_PORT . ";dbname=" . MYSQL_NAME . ";charset=utf8mb4";
                            self::$instance = new PDO($mysqlDsn, MYSQL_USER, MYSQL_PASS, $options);
                            self::$activeDriver = 'mysql';
                            return self::$instance;
                        } catch (Exception $fallbackEx) {
                            // Fall through to standard error handler
                        }
                    }
                }

                if (defined('IS_API_REQUEST') && IS_API_REQUEST) {
                    header('Content-Type: application/json; charset=utf-8');
                    http_response_code(500);
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Database connection failed. Please check your Supabase or database configuration in backend/.env.',
                        'error' => $e->getMessage()
                    ]);
                    exit;
                } else {
                    die("
                        <div style='font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif; max-width: 650px; margin: 50px auto; padding: 28px; border: 1px solid #E2E8F0; border-radius: 16px; background: #FFFFFF; box-shadow: 0 10px 25px rgba(0,0,0,0.05);'>
                            <div style='display: flex; align-items: center; margin-bottom: 16px;'>
                                <div style='background: #FEE2E2; border-radius: 50%; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; margin-right: 14px;'>
                                    <span style='color: #DC2626; font-size: 22px;'>⚠️</span>
                                </div>
                                <div>
                                    <h3 style='color: #1E293B; margin: 0; font-size: 18px;'>Database Connection Error</h3>
                                    <p style='color: #64748B; margin: 2px 0 0 0; font-size: 13px;'>Book Banko Supabase Migration</p>
                                </div>
                            </div>
                            <div style='background: #FEF2F2; border-left: 4px solid #EF4444; padding: 12px 16px; border-radius: 6px; margin-bottom: 20px;'>
                                <p style='color: #991B1B; font-size: 14px; margin: 0;'><strong>Details:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
                            </div>
                            <h4 style='color: #334155; margin-bottom: 8px; font-size: 14px;'>How to connect to Supabase:</h4>
                            <ol style='color: #475569; font-size: 13px; line-height: 1.6; padding-left: 20px;'>
                                <li>Open your <strong>Supabase Dashboard</strong>.</li>
                                <li>Go to <strong>Project Settings &gt; Database &gt; Connection Parameters</strong>.</li>
                                <li>Open <code>backend/.env</code> in your code editor.</li>
                                <li>Fill in <code>SUPABASE_DB_HOST</code>, <code>SUPABASE_DB_PASSWORD</code>, <code>SUPABASE_URL</code>, etc.</li>
                                <li>Run the migration script <code>backend/supabase_migration.sql</code> in the Supabase SQL Editor.</li>
                            </ol>
                        </div>
                    ");
                }
            }
        }
        return self::$instance;
    }

    public static function getDriver(): string {
        if (self::$instance === null) {
            self::getConnection();
        }
        return self::$activeDriver;
    }
}
