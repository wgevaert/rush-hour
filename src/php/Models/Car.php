<?php

namespace RushHour\Models;

use RangeException;
use UnexpectedValueException;

/**
 * A car from the RushHour game
 */
class Car
{
    public function __construct(
        public Coordinate $position,
        public readonly CarDirection $direction = CarDirection::DOWN,
        public readonly int $length = 2
    ) {
    }

    /**
     * @return array<Coordinate> The coordinates that are part of this car
     */
    public function getCoordinates(): array
    {
        $coordinates = [];

        $currentCoordinate = clone $this->position;

        for ($index = 0; $index < $this->length; $index++) {
            $coordinates [] = clone $currentCoordinate;

            if ($this->direction === CarDirection::RIGHT) {
                $currentCoordinate->x++;
            } else {
                $currentCoordinate->y++;
            }
        }

        return $coordinates;
    }

    public function __clone(): void
    {
        $this->position = clone $this->position;
    }
}
