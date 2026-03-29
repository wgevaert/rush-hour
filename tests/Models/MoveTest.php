<?php

namespace RushHour\Test\Models;

use PHPUnit\Framework\TestCase;
use RushHour\Models\Move;
use RushHour\Models\MoveDirection;

class MoveTest extends TestCase
{
    public function testCreate(): void
    {
        $move = new Move('MyCar', MoveDirection::NORTH, 3);
        $this->assertEquals('MyCar', $move->carName);
        $this->assertEquals(MoveDirection::NORTH, $move->direction);
        $this->assertEquals(3, $move->steps);
    }
}
