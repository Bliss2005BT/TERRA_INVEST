<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost');
}

if (!defined('DB_USER')) {
    define('DB_USER', 'root');
}

if (!defined('DB_PASS')) {
    define('DB_PASS', '');
}

if (!defined('DB_NAME')) {
    define('DB_NAME', 'terra_invest');
}

function getDBConnection(): mysqli
{
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    } catch (mysqli_sql_exception $exception) {
        throw new RuntimeException('Unable to connect to the database.', 0, $exception);
    }

    $conn->set_charset('utf8mb4');

    return $conn;
}

function closeDBConnection(mysqli $conn): void
{
    $conn->close();
}
?>
