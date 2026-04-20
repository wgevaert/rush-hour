<?php

namespace RushHour\Logger;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
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

    public function __construct(
        private ?ContextSerializer $serializer = null,
    ) {
        $this->serializer ??= new ContextSerializer();
    }

    /**
     * @param string $level
     * @param string|Stringable $message
     * @param array<mixed> $context
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
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
