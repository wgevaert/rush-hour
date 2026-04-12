<?php

namespace RushHour\Test\Serialization;

use PHPUnit\Framework\TestCase;
use RushHour\Models\Board;
use RushHour\Models\CarDirection;
use RushHour\Models\Coordinate;
use RushHour\Serialization\BoardDrawingParser;

class BoardDrawingParserTest extends TestCase
{
    public function testBoardFromDrawing(): void
    {
        $parser = new BoardDrawingParser();
        $boardDrawing = <<<BOARD
            @@@@@@@@
            @..r...@
            @..r...@
            @.aaa..@
            @@@.@@@@
            BOARD;

        $board = $parser->boardFromDrawing($boardDrawing);

        $this->assertEquals(new Coordinate(6, 3), $board->getBottomRight());
        $this->assertEquals(new Coordinate(3, 4), $board->getExit());

        $this->assertTrue($board->hasCar('r'));
        $rCar = $board->getCar('r');
        $this->assertEquals(new Coordinate(3, 1), $rCar->position);
        $this->assertEquals(CarDirection::DOWN, $rCar->direction);
        $this->assertEquals(2, $rCar->length);

        $this->assertTrue($board->hasCar('a'));
        $aCar = $board->getCar('a');
        $this->assertEquals(new Coordinate(2, 3), $aCar->position);
        $this->assertEquals(CarDirection::RIGHT, $aCar->direction);
        $this->assertEquals(3, $aCar->length);
    }

    public function testBoardFromDrawingCarInExitNorth(): void
    {
        $parser = new BoardDrawingParser();
        $boardDrawing = <<<BOARD
            @@@@r@@@
            @...r..@
            @......@
            @......@
            @@@@@@@@
            BOARD;

        $board = $parser->boardFromDrawing($boardDrawing);

        $this->assertEquals(new Coordinate(4, 0), $board->getExit());

        $this->assertTrue($board->hasCar('r'));
        $rCar = $board->getCar('r');
        $this->assertEquals(new Coordinate(4, 0), $rCar->position);
        $this->assertEquals(CarDirection::DOWN, $rCar->direction);
        $this->assertEquals(2, $rCar->length);
    }

    public function testBoardFromDrawingCarInExitEast(): void
    {
        $parser = new BoardDrawingParser();
        $boardDrawing = <<<BOARD
            @@@@@@@@
            @......@
            @.....rr
            @......@
            @@@@@@@@
            BOARD;

        $board = $parser->boardFromDrawing($boardDrawing);

        $this->assertEquals(new Coordinate(7, 2), $board->getExit());

        $this->assertTrue($board->hasCar('r'));
        $rCar = $board->getCar('r');
        $this->assertEquals(new Coordinate(6, 2), $rCar->position);
        $this->assertEquals(CarDirection::RIGHT, $rCar->direction);
        $this->assertEquals(2, $rCar->length);
    }

    public function testBoardFromDrawingCarInExitSouth(): void
    {
        $parser = new BoardDrawingParser();
        $boardDrawing = <<<BOARD
            @@@@@@@@
            @......@
            @......@
            @..r...@
            @@@r@@@@
            BOARD;

        $board = $parser->boardFromDrawing($boardDrawing);

        $this->assertEquals(new Coordinate(3, 4), $board->getExit());

        $this->assertTrue($board->hasCar('r'));
        $rCar = $board->getCar('r');
        $this->assertEquals(new Coordinate(3, 3), $rCar->position);
        $this->assertEquals(CarDirection::DOWN, $rCar->direction);
        $this->assertEquals(2, $rCar->length);
    }

    public function testBoardFromDrawingCarInExitWest(): void
    {
        $parser = new BoardDrawingParser();
        $boardDrawing = <<<BOARD
            @@@@@@@@
            @......@
            rr.....@
            @......@
            @@@@@@@@
            BOARD;

        $board = $parser->boardFromDrawing($boardDrawing);

        $this->assertEquals(new Coordinate(0, 2), $board->getExit());

        $this->assertTrue($board->hasCar('r'));
        $rCar = $board->getCar('r');
        $this->assertEquals(new Coordinate(0, 2), $rCar->position);
        $this->assertEquals(CarDirection::RIGHT, $rCar->direction);
        $this->assertEquals(2, $rCar->length);
    }

    public function testBoardFromDrawingComplicatedBoard(): void
    {
        $parser = new BoardDrawingParser();
        $boardDrawing = <<<BOARD
            @@@@@@@@
            @kkk.e.@
            @..j.e.@
            @rrj.ea.
            @ihhcca@
            @iggd.b@
            @fffd.b@
            @@@@@@@@
            BOARD;

        $board = $parser->boardFromDrawing($boardDrawing);

        $this->assertCount(12, $board->getCars());
    }
}
