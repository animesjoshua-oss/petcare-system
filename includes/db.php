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
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST, DB_NAME, DB_CHARSET
        );
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,  // throw exceptions on errors
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,  // return arrays by default
            PDO::ATTR_EMULATE_PREPARES => false,  // use real prepared statements
        ];
        
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Log the error but don't expose details to users
            error_log('DB Connection Error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Database connection failed. Please try again later.']);
            exit;
        }
    }
    
    return $pdo;
}
