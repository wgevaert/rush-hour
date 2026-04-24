<?php

namespace RushHour\Cli;

use RushHour\Logger\FileLogger;
use RushHour\Exception\UserErrorException;

class Entrypoint
{
    private CommandLineInputOutput $io;
    private array $argv = [];
    private array $args = [];
    private array $options = [];

    public function setIo(CommandLineInputOutput $io): void
    {
        $this->io = $io;
    }

    public function setArgv(array $argv): void
    {
        $this->argv = $argv;
    }

    public function run(): void
    {
        $this->parseArgs();
        try {
            $this->getEndpoint()->run();
        } catch (UserErrorException $exception) {
            $this->io->writeError($exception->getMessage());
        }
    }

    private function parseArgs(): void
    {
        foreach ($this->argv as $arg) {
            if (str_starts_with($arg, "--")) {
                $parts = explode("=", substr($arg, strlen("--")), 2);
                $key = $parts[0];
                $value = $parts[1] ?? true;
                $this->options[$key] = $value;
            } elseif (str_starts_with($arg, "-")) {
                $key = substr($arg, 1);
                $this->options[$key] = true;
            } else {
                $this->args[] = $arg;
            }
        }
    }

    private function getEndpoint(): CommandLineEndpoint
    {
        $endpoint = match ($this->options['action'] ?? 'solve') {
            'solve' => new SolveEndpoint(),
            default => throw new UserErrorException("Unknown action"),
        };
        $endpoint->setIo($this->io);
        $endpoint->setArgs($this->args);
        $endpoint->setOptions($this->options);
        $endpoint->setLogger((new FileLogger)->setLogLevel('error'));
        return $endpoint;
    }
}
