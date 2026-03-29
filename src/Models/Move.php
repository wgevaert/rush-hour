<?php

namespace RushHour\Models;

/**
 * A move of a named car
 */
class Move
{
    public function __construct(
        public readonly string $carName,
        public readonly MoveDirection $direction,
        public readonly int $steps = 1
    ) {
    }
}
