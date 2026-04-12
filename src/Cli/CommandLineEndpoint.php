<?php

namespace RushHour\Cli;

use Psr\Log\LoggerAwareInterface;

interface CommandLineEndpoint extends LoggerAwareInterface
{
    public function setIO(CommandLineInputOutput $io): void;
    public function setArgs(array $args): void;
    public function setOptions(array $options): void;
    public function run(): void;
}