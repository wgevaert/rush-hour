<?php

namespace RushHour\Serialization;

use LogicException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use RushHour\Exception\SerializedException;
use RushHour\Models\Board;
use RushHour\Models\Car;
use RushHour\Models\CarDirection;
use RushHour\Models\Coordinate;
use RushHour\Services\Player;

/**
 * Can make a Board instance from a drawn board
 *
 * Example of drawn board:
 * @@@@@@@@
 * @..r...@
 * @..r...@
 * @.aaa..@
 * @@@.@@@@
 *
 * This example should give a board of size 6x3 with the exit at 3,4 and the following cars:
 * - A car "r" at 3,1 of length 2 looking down
 * - A car "a" at 2,3 of length 3 looking right
 */
class BoardDrawingParser implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const BORDER = '@';
    public const EMPTY = '.';

    /** @var list<string> $lines */
    private array $lines;

    public function boardFromDrawing(string $drawing): Board
    {
        $this->lines = explode("\n", $drawing);
        $sizeX = strlen($this->lines[0]) - 2;
        $sizeY = count($this->lines) - 2;

        $board = new Board($sizeX, $sizeY);

        foreach ($this->getCars() as $carName => $car) {
            $board->addCar($car, $carName);
        }

        $this->setExit($board);

        $this->lines = [];

        return $board;
    }

    private function setExit(Board $board): void
    {
        $exit = $this->getExit($board);
        if ($exit === null) {
            $exit = $this->guessExit($board);
        }
        $board->setExit($exit);
    }

    /**
     * Gets the first position on the border that is not self::BORDER
     *
     * @param int $sizeX
     * @param int $sizeY
     */
    private function getExit(Board $board): Coordinate
    {
        $sizeX = $board->getBottomRight()->x;
        $sizeY = $board->getBottomRight()->y;

        for ($x = 0; $x < $sizeX + 2; $x++) {
            if ($this->lines[ 0 ][ $x ] !== self::BORDER) {
                return new Coordinate($x, 0);
            }
            if ($this->lines[ $sizeY + 1 ][ $x ] !== self::BORDER) {
                return new Coordinate($x, $sizeY + 1);
            }
        }
        for ($y = 1; $y < $sizeY; $y++) {
            if ($this->lines[ $y ][ 0 ] !== self::BORDER) {
                return new Coordinate(0, $y);
            }
            if ($this->lines[ $y ][ $sizeX + 1 ] !== self::BORDER) {
                return new Coordinate($sizeX + 1, $y);
            }
        }
        return null;
    }

    /**
     * Guesses the exit location based on the position of the goal car
     *
     * The exit is always in line with the goal car if that exists.
     * If the board is 6x6 and the exit can be on 7,4 (the de facto standard position), put it there,
     * Otherwise put it at x-coordinate 0 or y-coordinate 0 in line with the goal car.
     */
    private function guessExit(Board $board): Coordinate
    {
        $sizeX = $board->getBottomRight()->x;
        $sizeY = $board->getBottomRight()->y;

        $objectiveCarName = Player::GOAL_CAR;
        if (!$board->hasCar($objectiveCarName)) {
            // Exit in normal game is at the far-X border with y-coordinate 4.
            $exitX = $sizeX + 1;
            $exitY = min(4, $sizeY);
            return new Coordinate($exitX, $exitY);
        }
        $objectiveCar = $board->getCar($objectiveCarName);
        if ($objectiveCar->direction === CarDirection::RIGHT) {
            if ( $sizeX === 6 && $sizeY === 6 && $objectiveCar->position->y === 4) {
                // In the standard 6x6 game, the exit is at 7,4, so we put it there if possible.
                return new Coordinate(7, 4);
            }
            return new Coordinate(0, $objectiveCar->position->y);
        }
        return new Coordinate($objectiveCar->position->x, 0);
    }


    /**
     * Reads all the cars from the drawing
     * @return array<string, Car> Names pointing to cars
     */
    private function getCars(): array
    {
        $cars = [];

        for ($y = 0; $y < count($this->lines) - 1; $y++) {
            $line = $this->lines[$y];
            for ($x = 0; $x < strlen($line) - 1; $x++) {
                if ($line[$x] === self::EMPTY || $line[$x] === self::BORDER) {
                    continue;
                }

                $name = $line[$x];
                $position = new Coordinate($x, $y);
                if (isset($cars[ $name ])) {
                    if (!in_array($position, $cars[ $name ]->getCoordinates())) {
                        throw new SerializedException("A car with the same name appeared twice in the drawing: $name");
                    }
                    continue;
                }
                $cars[ $name ] = $this->getCar($position);
            }
        }
        return $cars;
    }

    private function getCar(Coordinate $position): Car
    {
        $name = $this->lines[ $position->y ][ $position->x ];
        if ($this->lines[ $position->y ][ $position->x + 1 ] === $name) {
            return $this->getCarFacingRight($position);
        }
        if ($this->lines[ $position->y + 1 ][ $position->x ] === $name) {
            return $this->getCarFacingDown($position);
        }

        // A length 1 car...
        return new Car($position);
    }

    private function getCarFacingRight(Coordinate $position): Car
    {
        $name = $this->getCharAt($position->x, $position->y);
        $length = 2;
        while ($this->getCharAt($position->x + $length, $position->y) === $name) {
            $length++;
        }

        return new Car($position, CarDirection::RIGHT, $length);
    }

    private function getCarFacingDown(Coordinate $position): Car
    {
        $name = $this->getCharAt($position->x, $position->y);
        $length = 2;
        while ($this->getCharAt($position->x, $position->y + $length) === $name) {
            $length++;
        }

        return new Car($position, CarDirection::DOWN, $length);
    }

    private function getCharAt(int $x, int $y): string
    {
        if ($this->outOfBounds($x, $y)) {
            return self::BORDER;
        }
        return $this->lines[$y][$x];
    }

    private function outOfBounds(int $x, int $y): bool
    {
        return $x < 0 || $y < 0 || $y >= count($this->lines) || $x >= strlen($this->lines[$y]);
    }
}
