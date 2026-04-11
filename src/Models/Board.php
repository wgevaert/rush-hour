<?php

namespace RushHour\Models;

use RangeException;
use OutOfBoundsException;

/**
 * A RushHour board
 *
 * It has a size, cars, and an exit.
 */
class Board
{
    /** @var array<Car> $cars */
    private array $cars = [];

    private Coordinate $exit;
    private ?Coordinate $translatedExit = null;

    private bool $carsSorted = true;

    public function __construct(
        private readonly int $boardSizeX,
        private readonly int $boardSizeY
    ) {
        if ($boardSizeX < 0 || $boardSizeY < 0) {
            throw new RangeException("Board must have a non-negative size");
        }
    }

    public function __clone(): void
    {
        foreach ($this->cars as &$car) {
            $car = clone $car;
        }
        $this->exit = clone $this->exit;
    }

    public function setExit(Coordinate $exit): void
    {
        $this->exit = $this->clampForExit($exit);
    }

    public function isExit(Coordinate $exit): bool
    {
        if ($this->isOnBoard($exit)) {
            return $this->isNearExit($exit);
        }
        $clamped = $this->clampForExit($exit);
        return $this->exit == $clamped;
    }

    public function isNearExit(Coordinate $exit): bool
    {
        return $this->getExitOnBoard() == $exit;
    }

    public function getExit(): Coordinate
    {
        return $this->exit;
    }

    public function getExitOnBoard(): Coordinate
    {
        if ( $this->translatedExit === null ) {
            $translatedExit = clone $this->exit;
            if ( $translatedExit->x === 0 ) {
                $translatedExit->x++;
            }
            if ( $translatedExit->y === 0 ) {
                $translatedExit->y++;
            }
            if ( $translatedExit->x === $this->boardSizeX ) {
                $translatedExit->x--;
            }
            if ( $translatedExit->y === $this->boardSizeY ) {
                $translatedExit->y--;
            }
            $this->translatedExit = $translatedExit;
        }
        return $this->translatedExit;
    }

    public function addCar(Car $car, string $name): void
    {
        $this->cars[ $name ] = $car;
        $this->carsSorted = false;
    }

    public function hasCar(string $name): bool
    {
        return isset($this->cars[ $name ]);
    }

    public function removeCar(string $name): void
    {
        unset($this->cars[ $name ]);
    }

    public function getCar(string $name): Car
    {
        if (!$this->hasCar($name)) {
            throw new OutOfBoundsException("A car with this name does not exist: $name");
        }
        return $this->cars[ $name ];
    }

    public function sortCars(): void
    {
        if (!$this->carsSorted) {
            ksort($this->cars);
            $this->carsSorted = true;
        }
    }

    /**
     * @return array<Car>
     */
    public function getCars(): array
    {
        return $this->cars;
    }

    public function getMaxSize(): int
    {
        return max($this->boardSizeX, $this->boardSizeY);
    }

    public function getBottomRight(): Coordinate
    {
        return new Coordinate($this->boardSizeX, $this->boardSizeY);
    }

    public function isOnBoardOrBorder(Coordinate $tile): bool
    {
        return $tile->x >= 0 && $tile->x <= $this->boardSizeX + 1 && $tile->y >= 0 && $tile->y <= $this->boardSizeY + 1;
    }

    public function isOnBoard(Coordinate $tile): bool
    {
        return $tile->x > 0 && $tile->x <= $this->boardSizeX && $tile->y > 0 && $tile->y <= $this->boardSizeY;
    }

    private function clampForExit(Coordinate $exit): Coordinate
    {
        if ($this->isOnBoard($exit)) {
            throw new OutOfBoundsException("Exit should be outside the main board");
        }
        if ($exit->x < 0) {
            $exit->x = 0;
        } elseif ($exit->x > $this->boardSizeX) {
            $exit->x = $this->boardSizeX + 1;
        }
        if ($exit->y < 0) {
            $exit->y = 0;
        } elseif ($exit->y > $this->boardSizeY) {
            $exit->y = $this->boardSizeY + 1;
        }
        return $exit;
    }
}
