<?php

namespace RushHour\Services;

use LogicException;
use RushHour\Exception\SerializedException;
use RushHour\Models\Board;
use RushHour\Models\Car;
use RushHour\Models\CarDirection;
use RushHour\Models\Coordinate;

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
class BoardDrawingParser
{
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
        $board->setExit($this->getExit($sizeX, $sizeY));

        foreach ($this->getCars() as $carName => $car) {
            $board->addCar($car, $carName);
        }

        $this->lines = [];

        return $board;
    }

    /**
     * Gets the first position on the border that is not self::BORDER
     *
     * @param int $sizeX
     * @param int $sizeY
     */
    private function getExit(int $sizeX, int $sizeY): Coordinate
    {
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
        throw new SerializedException("No exit found in drawing");
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
        $name = $this->getCharAt( $position->x, $position->y );
        $length = 2;
        while ($this->getCharAt( $position->x, $position->y + $length ) === $name) {
            $length++;
        }

        return new Car($position, CarDirection::DOWN, $length);
    }

    private function getCharAt( int $x, int $y ): string {
        if ( $this->outOfBounds( $x, $y ) ) {
            return self::BORDER;
        }
        return $this->lines[$y][$x];
    }

    private function outOfBounds( int $x, int $y ): bool {
        return $x < 0 || $y < 0 || $y >= count( $this->lines ) || $x >= strlen( $this->lines[$y] );
    }
}
