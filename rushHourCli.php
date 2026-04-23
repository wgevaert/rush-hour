<?php

namespace RushHour;

use RushHour\Cli\Entrypoint;
use RushHour\Cli\FileReadWriteIO;

require_once __DIR__ . "/vendor/autoload.php";

$entrypoint = new Entrypoint();
$io = new FileReadWriteIO();
$io->setInput(fopen("php://stdin", "r"));
$io->setOutput(fopen("php://stdout", "w"));
$io->setErrorOutput(fopen("php://stderr", "w"));
$entrypoint->setIo($io);
$entrypoint->setArgv($argv);
$entrypoint->run();