<?php

namespace RushHour\Test\Hasher;

use PHPUnit\Framework\TestCase;
use RushHour\Models\Board;
use RushHour\Models\Car;
use RushHour\Models\Coordinate;
use RushHour\Hasher\TernaryBoardHasher;

class TernaryBoardHasherTest extends TestCase
{
    public function testHashBoard(): void
    {
        $board = new Board(10, 11);
        $board->addCar(new Car(new Coordinate(1, 1)), 'L');
        $board->addCar(new Car(new Coordinate(2, 3)), 'K');
        $board->addCar(new Car(new Coordinate(5, 2)), 'M');
        $board->addCar(new Car(new Coordinate(2, 2)), 'A');

        $hasher = new TernaryBoardHasher();
        $hash = $hasher->hashBoard($board);
        $this->assertSame("0200aa00a80006000000000000000000000000000000", bin2hex($hash));
    }
}
