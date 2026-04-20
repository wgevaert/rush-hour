<?php

namespace RushHour\Serialization;

use RushHour\Models\Move;
use RushHour\Models\MoveDirection;
use RushHour\Exception\SerializedException;

/**
 * Serializes and unserializes a Move
 */
class MoveSerializer
{
    public const NORTH = 'N';
    public const EAST = 'E';
    public const SOUTH = 'S';
    public const WEST = 'W';

    public function serializeMove(Move $move): string
    {
        $direction = match ($move->direction) {
            MoveDirection::NORTH => self::NORTH,
            MoveDirection::EAST => self::EAST,
            MoveDirection::SOUTH => self::SOUTH,
            MoveDirection::WEST => self::WEST,
        };
        return $move->carName . $move->steps . $direction;
    }

    public function unserializeMove(string $move): Move
    {
        $carNameLength = strcspn($move, '123456789');
        $carName = substr($move, 0, $carNameLength);

        $stepsAndDirection = substr($move, $carNameLength);
        $stepsString = substr($stepsAndDirection, 0, -1);
        $steps = filter_var($stepsString, FILTER_VALIDATE_INT);
        if (!is_int($steps)) {
            throw new SerializedException('Steps should be int');
        }

        $directionString = substr($stepsAndDirection, -1);
        $direction = match ($directionString) {
            self::NORTH => MoveDirection::NORTH,
            self::EAST => MoveDirection::EAST,
            self::SOUTH => MoveDirection::SOUTH,
            self::WEST => MoveDirection::WEST,
            default => throw new SerializedException("Unrecognised direction")
        };

        return new Move($carName, $direction, $steps);
    }
}
