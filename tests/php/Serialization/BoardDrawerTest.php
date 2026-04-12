<?php

namespace RushHour\Test\Serialization;

use PHPUnit\Framework\TestCase;
use RushHour\Models\Board;
use RushHour\Models\Car;
use RushHour\Models\CarDirection;
use RushHour\Models\Coordinate;
use RushHour\Serialization\BoardDrawer;

class BoardDrawerTest extends TestCase
{
    public function testDraw(): void
    {
        $board = new Board( 5,5 );
        $board->setExit( new Coordinate( 6,3 ) );
        $board->addCar( new Car( new Coordinate( 1,1 ), CarDirection::RIGHT, 3 ), 'a' );
        $board->addCar( new Car( new Coordinate( 5,3 ), CarDirection::DOWN ), 'b' );
        $board->addCar( new Car( new Coordinate( 4,2 ), CarDirection::DOWN, 3 ), 'c' );
        $board->addCar( new Car( new Coordinate( 2,4 ), CarDirection::RIGHT ), 'd' );

        $boardDrawer = new BoardDrawer();

        $drawing = $boardDrawer->draw($board);
        $this->assertSame(
            "@@@@@@@\n".
            "@aaa..@\n".
            "@...c.@\n".
            "@...cb.\n".
            "@.ddcb@\n".
            "@.....@\n".
            "@@@@@@@",
            $drawing
        );
    }
}
