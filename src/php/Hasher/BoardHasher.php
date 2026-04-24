<?php

namespace RushHour\Hasher;

use RushHour\Models\Board;

/**
 * A BoardHasher can be used to uniquely represent a board as a string with minimal length.
 */
interface BoardHasher
{
    /**
     * Makes a string from a Board, under these conditions:
     * 1. Board size and exit position never change between boards given
     * 2. Two different boards give different strings
     * 3. The same boards give the same string
     *
     * Two boards are considered the same if cars of the same name are in the same position.
     */
    public function hashBoard(Board $board): string;
}
