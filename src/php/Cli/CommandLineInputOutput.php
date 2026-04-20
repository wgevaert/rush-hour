<?php

namespace RushHour\Cli;

interface CommandLineInputOutput
{
    /**
     * Reads a line of input from the user. Returns null if there is no more input.
     */
    public function readLine(): ?string;

    /**
     * Writes output to the user.
     */
    public function writeLine(string $line): void;

    /**
     * Writes error output to the user.
     */
    public function writeError(string $line): void;
}
