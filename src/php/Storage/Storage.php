<?php

namespace RushHour\Storage;

use RushHour\Models\Board;

interface Storage
{
    /**
     * Stores a board, and returns an ID that can be used to retrieve the board.
     */
    public function storeBoard(Board $board): string;

    /**
     * Retrieves a board with ID $id, or returns null if the ID does not have a board associated.
     */
    public function fetchBoard(string $id): ?Board;
}
