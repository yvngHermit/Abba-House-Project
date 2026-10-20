<?php

$isAzure = getenv('DB_CONNECTION') === 'sqlsrv';

try {
    if ($isAzure) {
        // Azure SQL Server connection.
        $server = getenv('DB_SERVER');
        $database = getenv('DB_DATABASE');
        $username = getenv('DB_USERNAME');
        $password = getenv('DB_PASSWORD');

        if (!$server || !$database || !$username || $password === false) {
            throw new RuntimeException(
                'Azure database environment variables are missing.'
            );
        }

        $dsn = "sqlsrv:Server=$server;Database=$database;Encrypt=1;TrustServerCertificate=0";

        $pdo = new PDO($dsn, $username, $password);
    } else {
        // Local XAMPP MariaDB connection.
        $host = 'localhost';
        $dbname = 'abba_house_db';
        $username = 'root';
        $password = '';

        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

        $pdo = new PDO($dsn, $username, $password);
    }

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Database connection failed. Please try again later.');
} catch (RuntimeException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('Database configuration error.');
}
