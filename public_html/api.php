<?php

namespace RushHour;

use RushHour\Web\ApiHandler;
use RushHour\Logger\FileLogger;

require_once __DIR__ . "/../vendor/autoload.php";

$method = $_SERVER['REQUEST_METHOD'];
$params = match ($method) {
    'POST' => $_POST,
    'GET' => $_GET,
    default => throw new UnexpectedValueException('Invalid method', 405),
};

$logger = new FileLogger;
$apiHandler = new ApiHandler();
$apiHandler->setLogger( $logger );
$apiHandler->setParams( $params );

$response = $apiHandler->handleRequest();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: http://localhost:5173');
echo json_encode( $response );
