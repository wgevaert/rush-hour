<?php

namespace RushHour\Serialization;

use RushHour\Models\Board;

/**
 * Serializer that can serialize boards to strings and vice versa.
 *
 * It is assumed that these functions are inverses of each other
 */
interface BoardSerializer
{
    /**
     * @param Board $board The board to serialize
     * @return string The serialized board.
     */
    public function serializeBoard(Board $board): string;

    /**
     * @param string $serializedBoard The board to unserialize
     * @return Board The unserialized board;
     *
     * @throws SerializedException when the string could not be deserialized;
     */
    public function unserializeBoard(string $serializedBoard): Board;
}
