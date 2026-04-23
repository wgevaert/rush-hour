<?php

namespace RushHour\Storage;

use RushHour\Exception\SerializedException;
use RushHour\Models\Board;
use RushHour\Serialization\BoardSerializer;

/**
 * A "Storage" that stores the complete board in the ID.
 *
 * No actual storage backend is used.
 */
class SerializerStorage implements Storage
{
    private BoardSerializer $serializer;

    public function setSerializer(BoardSerializer $serializer)
    {
        $this->serializer = $serializer;
    }

    public function storeBoard(Board $board): string
    {
        return $this->serializer->serializeBoard($board);
    }

    public function fetchBoard(string $id): ?Board
    {
        try {
            return $this->serializer->unserializeBoard($id);
        } catch (SerializedException $_) {
            return null;
        }
    }
}
