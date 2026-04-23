<?php

namespace RushHour\Cli;

class FileReadWriteIO implements CommandLineInputOutput
{
    /** @var resource|null */
    private $input;

    /** @var resource|null */
    private $output;

    /** @var resource|null */
    private $errorOutput;

    public function setInput($input): void
    {
        $this->input = $input;
    }

    public function setOutput($output): void
    {
        $this->output = $output;
    }

    public function setErrorOutput($errorOutput): void
    {
        $this->errorOutput = $errorOutput;
    }

    public function readLine(): ?string
    {
        if ($this->input === null) {
            return null;
        }
        if (feof($this->input)) {
            return null;
        }
        return fgets($this->input);
    }

    public function writeLine(string $line): void
    {
        if ($this->output === null) {
            return;
        }
        fwrite($this->output, $line . PHP_EOL);
    }

    public function writeError(string $line): void
    {
        if ($this->errorOutput === null) {
            return;
        }
        fwrite($this->errorOutput, $line . PHP_EOL);
    }
}
