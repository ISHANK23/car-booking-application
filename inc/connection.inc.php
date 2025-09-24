<?php
declare(strict_types=1);

require_once __DIR__ . '/security.inc.php';

$config = [
    'host' => getenv('DB_HOST') ?: null,
    'username' => getenv('DB_USER') ?: null,
    'password' => getenv('DB_PASSWORD') ?: null,
    'database' => getenv('DB_NAME') ?: null,
];

$configFile = __DIR__ . '/config.php';
if (file_exists($configFile)) {
    /** @var array{host?:string,username?:string,password?:string,database?:string} $fileConfig */
    $fileConfig = require $configFile;
    $config = array_merge($config, array_filter($fileConfig));
}

foreach (['host', 'username', 'database'] as $requiredKey) {
    if (empty($config[$requiredKey])) {
        throw new RuntimeException('Database configuration is incomplete.');
    }
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $con = new mysqli(
        (string) $config['host'],
        (string) $config['username'],
        (string) ($config['password'] ?? ''),
        (string) $config['database']
    );
    $con->set_charset('utf8mb4');
} catch (mysqli_sql_exception $exception) {
    error_log('Database connection failed: ' . $exception->getMessage());
    http_response_code(500);
    exit('Unable to connect to the database.');
}
