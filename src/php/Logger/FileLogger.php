<?php

namespace RushHour\Logger;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;
use RushHour\Models\Board;
use RushHour\Models\Move;
use RushHour\Serialization\BoardDrawer;
use RushHour\Serialization\MoveSerializer;
use Stringable;

/**
 * A quickly implemented logger that writes to a hard-coded file path.
 * Not for production use.
 */
class FileLogger implements LoggerInterface
{
    use LoggerTrait;

    private int $logLevel = 0;

    public function __construct(
        private ?ContextSerializer $serializer = null,
    ) {
        $this->serializer ??= new ContextSerializer();
    }

    public function setLogLevel(string $logLevel): self {
        $this->logLevel = $this->logLevelToInt($logLevel);
        return $this;
    }

    private function logLevelToInt(string $logLevel) {
        return match ($logLevel) {
            LogLevel::EMERGENCY => 7,
            LogLevel::ALERT     => 6,
            LogLevel::CRITICAL  => 5,
            LogLevel::ERROR     => 4,
            LogLevel::WARNING   => 3,
            LogLevel::NOTICE    => 2,
            LogLevel::INFO      => 1,
            LogLevel::DEBUG     => 0,
            default => 0,
        };
    }

    /**
     * @param string $level
     * @param string|Stringable $message
     * @param array<mixed> $context
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        if ($this->logLevelToInt($level) < $this->logLevel) {
            return;
        }
        file_put_contents(
            __DIR__ . "/../../../../LOGFILE",
            $this->formatLogLine($level, (string) $message, $context),
            FILE_APPEND
        );
    }

    /**
     * @param string $level
     * @param string|Stringable $message
     * @param array<mixed> $context
     */
    private function formatLogLine($level, string|Stringable $message, array $context): string
    {
        return "\n\n[$level] $message\n"
            . $this->serializer->formatContext($context)
            . "\n" . str_repeat('-', 80);
    }
}
