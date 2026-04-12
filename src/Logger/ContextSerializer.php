<?php

namespace RushHour\Logger;

use RushHour\Models\Board;
use RushHour\Models\Move;
use RushHour\Serialization\BoardDrawer;
use RushHour\Serialization\MoveSerializer;

class ContextSerializer
{
    public function __construct(
        private ?BoardDrawer $boardDrawer = null,
        private ?MoveSerializer $moveSerializer = null
    ) {
        $this->boardDrawer ??= new BoardDrawer();
        $this->moveSerializer ??= new MoveSerializer();
    }

    public function formatContext(mixed $context): string
    {
        if (is_array($context) && !empty($context)) {
            $formatted = 'CONTEXT:';
            foreach ($context as $key => $value) {
                if (is_object($value)) {
                    $value = match (get_class($value)) {
                        Board::class => $this->boardDrawer->draw($value),
                        Move::class => $this->moveSerializer->serializeMove($value),
                        default => (json_encode($value) ?: 'unserializeable') . ' (' . get_class($value) . ')'
                    };
                } elseif (!is_string($value)) {
                    $value = json_encode($value, JSON_PRETTY_PRINT);
                }
                $formatted .= "\n$key:\n$value\n--";
            }
            return $formatted;
        }
        return json_encode($context) ?: 'context corrupted';
    }
}
