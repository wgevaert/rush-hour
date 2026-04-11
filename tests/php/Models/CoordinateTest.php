<?php

namespace RushHour\Test\Models;

use PHPUnit\Framework\TestCase;
use RushHour\Models\Coordinate;

class CoordinateTest extends TestCase
{
    public function testCreate(): void
    {
        $coordinate = new Coordinate(2, 3);
        $this->assertEquals(2, $coordinate->x);
        $this->assertEquals(3, $coordinate->y);
    }
}
