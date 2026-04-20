<?php

namespace RushHour\Services;

use RushHour\Models\Board;

/**
 * A simple hasher that gives a string of cars and their positions
 */
class CarPositionBoardHasher implements BoardHasher
{
    public function hashBoard(Board $board): string
    {
        $board->sortCars();
        $hash = '';
        foreach ($board->getCars() as $name => $car) {
            $hash .= $name . $car->position->x . ',' . $car->position->y;
        }
        return $hash;
    }
}
