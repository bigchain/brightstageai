<?php
/**
 * Database connection via PDO.
 * Returns a singleton PDO instance configured for MySQL.
 * NEVER exposes credentials in error messages.
 */

require_once __DIR__ . '/env.php';

function get_db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $host = env('DB_HOST', 'localhost');
        $name = env('DB_NAME');
        $user = env('DB_USER');
        $pass = env('DB_PASS');

        if (!$name || !$user) {
            error_log('BrightStage: Missing required database environment variables (DB_NAME, DB_USER)');
            http_response_code(500);
            echo 'Something went wrong. Please try again later.';
            exit;
        }

        try {
            $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            // Log the real error, show generic message to user
            error_log('BrightStage DB connection failed: ' . $e->getMessage());
            http_response_code(500);
            echo 'Something went wrong. Please try again later.';
            exit;
        }
    }

    return $pdo;
}
