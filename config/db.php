<?php
declare(strict_types=1);

$DB_HOST = 'localhost';
$DB_NAME = 'mangapitch';
$DB_USER = 'root';
$DB_PASS = '';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
    $conn->set_charset('utf8mb4');
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Database connection failed.';
    exit;
}
