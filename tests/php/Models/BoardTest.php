<?php

namespace RushHour\Test\Models;

use PHPUnit\Framework\TestCase;
use RushHour\Models\Board;
use RushHour\Models\Car;
use RushHour\Models\Coordinate;

class BoardTest extends TestCase
{
    public function testCreate(): void
    {
        $board = new Board(10, 11);

        $this->assertSame(10, $board->getBottomRight()->x);
        $this->assertSame(11, $board->getBottomRight()->y);
    }

    public function testSetExit(): void
    {
        $board = new Board(10, 11);
        $exit = new Coordinate(0, 1);
        $board->setExit($exit);

        $this->assertEquals($exit, $board->getExit());
    }

    public function testIsExit(): void
    {
        $board = new Board(10, 11);
        $exit = new Coordinate(0, 1);
        $board->setExit($exit);

        $this->assertTrue($board->isExit($exit));
    }

    public function testAddCar(): void
    {
        $board = new Board(10, 11);
        $car = new Car(new Coordinate(1, 1));

        $board->addCar($car, 'Lightning McQueen');
        $this->assertEquals($car, $board->getCar('Lightning McQueen'));
    }

    public function testRemoveCar(): void
    {
        $board = new Board(10, 11);
        $car = new Car(new Coordinate(1, 1));

        $board->addCar($car, 'Lightning McQueen');
        $this->assertTrue($board->hasCar('Lightning McQueen'));

        $board->removeCar('Lightning McQueen');
        $this->assertFalse($board->hasCar('Lightning McQueen'));
    }

    public function testGetMaxSize(): void
    {
        $board = new Board(10, 15);
        $this->assertSame(15, $board->getMaxSize());

        $board = new Board(11, 9);
        $this->assertSame(11, $board->getMaxSize());
    }

    public function testIsOnBoardOrBorder(): void
    {
        $board = new Board(10, 11);
        $this->assertTrue($board->isOnBoardOrBorder(new Coordinate(0, 0)));
        $this->assertTrue($board->isOnBoardOrBorder(new Coordinate(1, 1)));
        $this->assertTrue($board->isOnBoardOrBorder(new Coordinate(0, 12)));
        $this->assertTrue($board->isOnBoardOrBorder(new Coordinate(11, 0)));
        $this->assertTrue($board->isOnBoardOrBorder(new Coordinate(11, 12)));
        $this->assertTrue($board->isOnBoardOrBorder(new Coordinate(5, 5)));

        $this->assertFalse($board->isOnBoardOrBorder(new Coordinate(-1, 0)));
        $this->assertFalse($board->isOnBoardOrBorder(new Coordinate(10, -1)));
        $this->assertFalse($board->isOnBoardOrBorder(new Coordinate(12, 0)));
        $this->assertFalse($board->isOnBoardOrBorder(new Coordinate(0, 13)));
    }
}
