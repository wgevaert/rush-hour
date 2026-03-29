<?php

namespace RushHour\Test\Models;

use PHPUnit\Framework\TestCase;
use RushHour\Models\Car;
use RushHour\Models\CarDirection;
use RushHour\Models\Coordinate;

class CarTest extends TestCase
{
    public function testCreate(): void
    {
        $car = new Car(new Coordinate(1, 3));
        $this->assertSame(1, $car->position->x);
        $this->assertSame(3, $car->position->y);
        $this->assertSame(CarDirection::DOWN, $car->direction);
        $this->assertSame(2, $car->length);
    }

    public function testGetCoordinatesDown(): void
    {
        $car = new Car(new Coordinate(2, 4), CarDirection::DOWN, 3);
        $this->assertContainsEquals(new Coordinate(2, 4), $car->getCoordinates());
        $this->assertContainsEquals(new Coordinate(2, 5), $car->getCoordinates());
        $this->assertContainsEquals(new Coordinate(2, 6), $car->getCoordinates());
    }

    public function testGetCoordinatesRight(): void
    {
        $car = new Car(new Coordinate(1, 3), CarDirection::RIGHT, 4);
        $this->assertContainsEquals(new Coordinate(1, 3), $car->getCoordinates());
        $this->assertContainsEquals(new Coordinate(2, 3), $car->getCoordinates());
        $this->assertContainsEquals(new Coordinate(3, 3), $car->getCoordinates());
        $this->assertContainsEquals(new Coordinate(4, 3), $car->getCoordinates());
    }

    public function testClone(): void
    {
        $car = new Car(new Coordinate(1, 2));
        $anotherCar = clone $car;
        $anotherCar->position->x = 2;
        $this->assertSame(1, $car->position->x);
        $this->assertSame(2, $anotherCar->position->x);
    }
}
