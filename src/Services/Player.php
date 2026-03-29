<?php

namespace RushHour\Services;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use RushHour\Models\Board;
use RushHour\Models\Car;
use RushHour\Models\CarDirection;
use RushHour\Models\Coordinate;
use RushHour\Models\Move;
use RushHour\Models\MoveDirection;

/**
 * A player has a board and understands the rules of the game
 *
 * The player can make a move on its board, and answer questions such as:
 * - Is this a valid move?
 * - Is the puzzle solved?
 * - What are the current possible moves?
 */
class Player implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const GOAL_CAR = 'r';

    public function __construct(private Board $board)
    {
    }

    public function getBoard(): Board
    {
        return $this->board;
    }

    public function makeMove(Move $move): void
    {
        $carName = $move->carName;

        $car = $this->board->getCar($carName);
        $this->moveCar($car, $move);
    }

    public function makeMoveBackwards(Move $move): void
    {
        $this->makeMove($this->reverseMove($move));
    }

    /**
     * @return iterable<Move> The possible moves for the current board
     */
    public function getPossibleMoves(): iterable
    {
        foreach ($this->board->getCars() as $carName => $car) {
            foreach ($this->getPossibleMoveDirections($car->direction) as $direction) {
                $steps = 1;
                while ($steps < $this->board->getMaxSize()) {
                    $move = new Move($carName, $direction, $steps);
                    if ($this->isPossibleMove($move)) {
                        yield $move;
                    } else {
                        break;
                    }
                    $steps++;
                }
            }
        }
    }

    public function __clone(): void
    {
        $this->board = clone $this->board;
    }

    public function puzzleSolved(): bool
    {
        if (!$this->board->hasCar(self::GOAL_CAR)) {
            // If the goal car is not on the board, the puzzle cannot be solved any further
            return true;
        }
        $goalCar = $this->board->getCar(self::GOAL_CAR);
        foreach ($goalCar->getCoordinates() as $carPart) {
            if ($this->board->isExit($carPart)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return iterable<MoveDirection>
     */
    private function getPossibleMoveDirections(CarDirection $carDirection): iterable
    {
        return match ($carDirection) {
            CarDirection::RIGHT => [ MoveDirection::EAST, MoveDirection::WEST ],
            CarDirection::DOWN => [ MoveDirection::NORTH, MoveDirection::SOUTH ]
        };
    }

    private function isPossibleMove(Move $move): bool
    {
        $carName = $move->carName;

        $car = $this->board->getCar($move->carName);
        $movedCar = $this->moveCar(clone $car, $move);
        foreach ($movedCar->getCoordinates() as $tile) {
            if (!$this->isDrivable($tile, $carName)) {
                return false;
            }
            if (!$this->board->isOnBoardOrBorder($movedCar->position)) {
                // Although the car could move indefinitely after the exit, we say it cannot move past the border.
                return false;
            }
        }

        return true;
    }

    private function moveCar(Car $car, Move $move): Car
    {
        $position = $car->position;
        match ($move->direction) {
            MoveDirection::NORTH => $position->y -= $move->steps,
            MoveDirection::EAST => $position->x += $move->steps,
            MoveDirection::SOUTH => $position->y += $move->steps,
            MoveDirection::WEST => $position->x -= $move->steps,
        };
        return $car;
    }

    private function reverseMove(Move $move): Move
    {
        $reversedDirection = match ($move->direction) {
            MoveDirection::NORTH => MoveDirection::SOUTH,
            MoveDirection::EAST => MoveDirection::WEST,
            MoveDirection::SOUTH => MoveDirection::NORTH,
            MoveDirection::WEST => MoveDirection::EAST,
        };
        return new Move($move->carName, $reversedDirection, $move->steps);
    }

    private function isDrivable(Coordinate $tile, ?string $carNameToIgnore = null): bool
    {
        if (!$this->board->isOnBoard($tile)) {
            $this->logger?->debug(
                'Tile is maybe exit',
                [ 'board' => $this->board, 'tile' => $tile, 'name' => $carNameToIgnore ]
            );
            return $this->board->isExit($tile);
        }
        foreach ($this->board->getCars() as $carName => $car) {
            if ($carName === $carNameToIgnore) {
                continue;
            }
            if (in_array($tile, $car->getCoordinates())) {
                $this->logger?->debug(
                    'Tile is part of other car',
                    [ 'other car' => $carName, 'board' => $this->board, 'tile' => $tile, 'name' => $carNameToIgnore ]
                );
                return false;
            }
        }
        return true;
    }
}
