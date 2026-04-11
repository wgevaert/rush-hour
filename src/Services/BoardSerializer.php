<?php

namespace RushHour\Services;

use RushHour\Models\Board;

/**
 * Serializer that can serialize boards to strings and vice versa.
 *
 * It is assumed that these functions are inverses of each other
 */
interface BoardSerializer
{
    public function serializeBoard(Board $board): string;

    public function unserializeBoard(string $serializedBoard): Board;
}
