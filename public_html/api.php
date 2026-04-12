<?php

namespace RushHour;

use RushHour\Web\Entrypoint;

require_once __DIR__ . "/../vendor/autoload.php";

$entrypoint = new Entrypoint();
$entrypoint->setPost($_POST);
$entrypoint->setGet($_GET);
$entrypoint->setServer($_SERVER);

$response = $entrypoint->run();
foreach ($response->getHeaders() as $header) {
    header($header);
}
http_response_code($response->getCode());
echo $response->getBody();
