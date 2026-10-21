
<?php
if (!extension_loaded('pdo_sqlsrv')) {
    exit("FAIL: The pdo_sqlsrv extension is not loaded.\n");
}

$server = getenv('DB_SERVER');
$database = getenv('DB_DATABASE');
$username = getenv('DB_USERNAME');
$password = getenv('DB_PASSWORD');

if (!$server || !$database || !$username || $password === false) {
    exit(
        "FAIL: Azure connection variables are not set in this terminal.\n"
    );
}

try {
    $dsn = "sqlsrv:Server=$server;Database=$database;Encrypt=1;TrustServerCertificate=0";

    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $result = $pdo->query(
        "SELECT DB_NAME() AS database_name, @@VERSION AS sql_version"
    )->fetch();

    echo "SUCCESS: Connected to Azure SQL!\n";
    echo "Database: " . $result['database_name'] . "\n";

} catch (Throwable $e) {
    error_log("Azure connection test failed: " . $e->getMessage());
    echo "FAIL: Connection unsuccessful. Check the server, credentials, firewall, and driver configuration.\n";
    echo "Error details have been written to the PHP error log.\n";
}
