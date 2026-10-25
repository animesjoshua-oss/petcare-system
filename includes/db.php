<?php
/**
 * Database Connection Handler
 * Uses PDO for secure database operations
 * 
 * Returns a singleton PDO instance to avoid multiple connections
 */
require_once __DIR__ . '/config.php';

function getDB(): PDO {
    static $pdo = null;
    
    // Reuse existing connection if available
    if ($pdo === null) {
        $port = defined('DB_PORT') ? DB_PORT : '3306';
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST, $port, DB_NAME, DB_CHARSET
        );
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,  // throw exceptions on errors
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,  // return arrays by default
            PDO::ATTR_EMULATE_PREPARES => false,  // use real prepared statements
        ];
        
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Log the error
            error_log('DB Connection Error: ' . $e->getMessage());
            
            // Show error details for debugging
            header('Content-Type: application/json');
            http_response_code(500);
            die(json_encode([
                'error' => 'Database connection failed.',
                'details' => $e->getMessage(),
                'host' => DB_HOST,
                'database' => DB_NAME,
                'user' => DB_USER
            ]));
        }
    }
    
    return $pdo;
}
