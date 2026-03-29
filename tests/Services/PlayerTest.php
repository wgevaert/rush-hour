<?php

namespace RushHour\Test\Services;

use PHPUnit\Framework\TestCase;
use RushHour\Models\Board;
use RushHour\Models\Car;
use RushHour\Models\CarDirection;
use RushHour\Models\Coordinate;
use RushHour\Models\Move;
use RushHour\Models\MoveDirection;
use RushHour\Services\BoardDrawingParser;
use RushHour\Services\Player;

class PlayerTest extends TestCase
{
    public function testGetBoard(): void
    {
        $board = new Board(1, 1);
        $player = new Player($board);
        $this->assertEquals($board, $player->getBoard());
    }

    public function testMakeMoveSouthNorth(): void
    {
        $car = new Car(new Coordinate(1, 1));
        $board = new Board(1, 4);
        $board->addCar($car, 'r');

        $player = new Player($board);

        $moveSouth = new Move('r', MoveDirection::SOUTH, 2);
        $player->makeMove($moveSouth);
        $this->assertEquals(new Coordinate(1, 3), $car->position);

        $moveNorth = new Move('r', MoveDirection::NORTH, 1);
        $player->makeMove($moveNorth);
        $this->assertEquals(new Coordinate(1, 2), $car->position);
    }

    public function testMakeMoveEastWest(): void
    {
        $car = new Car(new Coordinate(1, 1), CarDirection::RIGHT);
        $board = new Board(4, 1);
        $board->addCar($car, 'r');

        $player = new Player($board);

        $moveEast = new Move('r', MoveDirection::EAST, 2);
        $player->makeMove($moveEast);
        $this->assertEquals(new Coordinate(3, 1), $car->position);

        $moveWest = new Move('r', MoveDirection::WEST, 1);
        $player->makeMove($moveWest);
        $this->assertEquals(new Coordinate(2, 1), $car->position);
    }

    public function testMakeMoveBackwardsNorthSouth(): void
    {
        $car = new Car(new Coordinate(1, 1));
        $board = new Board(1, 4);
        $board->addCar($car, 'r');

        $player = new Player($board);

        $moveNorth = new Move('r', MoveDirection::NORTH, 2);
        $player->makeMoveBackwards($moveNorth);
        $this->assertEquals(new Coordinate(1, 3), $car->position);

        $moveSouth = new Move('r', MoveDirection::SOUTH, 1);
        $player->makeMoveBackwards($moveSouth);
        $this->assertEquals(new Coordinate(1, 2), $car->position);
    }

    public function testMakeMoveBackwardsWestEast(): void
    {
        $car = new Car(new Coordinate(1, 1), CarDirection::RIGHT);
        $board = new Board(4, 1);
        $board->addCar($car, 'r');

        $player = new Player($board);

        $moveWest = new Move('r', MoveDirection::WEST, 2);
        $player->makeMoveBackwards($moveWest);
        $this->assertEquals(new Coordinate(3, 1), $car->position);

        $moveEast = new Move('r', MoveDirection::EAST, 1);
        $player->makeMoveBackwards($moveEast);
        $this->assertEquals(new Coordinate(2, 1), $car->position);
    }

    public function testGetPossibleMovesSouth(): void
    {
        $car = new Car(new Coordinate(1, 1));
        $board = new Board(2, 4);
        $board->addCar($car, 'r');
        $board->setExit(new Coordinate(2, 0));

        $player = new Player($board);

        $possibleMoves = iterator_to_array($player->getPossibleMoves());
        $this->assertCount(2, $possibleMoves);
        foreach ($possibleMoves as $move) {
            $this->assertSame(MoveDirection::SOUTH, $move->direction);
        }
    }

    public function testGetPossibleMovesSouthToExit(): void
    {
        $car = new Car(new Coordinate(1, 1));
        $board = new Board(1, 4);
        $board->addCar($car, 'r');
        $board->setExit(new Coordinate(1, 5));

        $player = new Player($board);

        $possibleMoves = iterator_to_array($player->getPossibleMoves());
        $this->assertCount(3, $possibleMoves);
        foreach ($possibleMoves as $move) {
            $this->assertSame(MoveDirection::SOUTH, $move->direction);
        }
    }

    public function testGetPossibleMovesNorth(): void
    {
        $car = new Car(new Coordinate(1, 3));
        $board = new Board(2, 4);
        $board->addCar($car, 'r');
        $board->setExit(new Coordinate(2, 0));

        $player = new Player($board);

        $possibleMoves = iterator_to_array($player->getPossibleMoves());
        $this->assertCount(2, $possibleMoves);
        foreach ($possibleMoves as $move) {
            $this->assertSame(MoveDirection::NORTH, $move->direction);
        }
    }

    public function testGetPossibleMovesNorthToExit(): void
    {
        $car = new Car(new Coordinate(1, 3));
        $board = new Board(1, 4);
        $board->addCar($car, 'r');
        $board->setExit(new Coordinate(1, 0));

        $player = new Player($board);

        $possibleMoves = iterator_to_array($player->getPossibleMoves());
        $this->assertCount(3, $possibleMoves);
        foreach ($possibleMoves as $move) {
            $this->assertSame(MoveDirection::NORTH, $move->direction);
        }
    }

    public function testGetPossibleMovesEast(): void
    {
        $car = new Car(new Coordinate(1, 1), CarDirection::RIGHT);
        $board = new Board(4, 2);
        $board->addCar($car, 'r');
        $board->setExit(new Coordinate(0, 2));

        $player = new Player($board);

        $possibleMoves = iterator_to_array($player->getPossibleMoves());
        $this->assertCount(2, $possibleMoves);
        foreach ($possibleMoves as $move) {
            $this->assertSame(MoveDirection::EAST, $move->direction);
        }
    }

    public function testGetPossibleMovesEastToExit(): void
    {
        $car = new Car(new Coordinate(1, 1), CarDirection::RIGHT);
        $board = new Board(4, 1);
        $board->addCar($car, 'r');
        $board->setExit(new Coordinate(5, 1));

        $player = new Player($board);

        $possibleMoves = iterator_to_array($player->getPossibleMoves());
        $this->assertCount(3, $possibleMoves);
        foreach ($possibleMoves as $move) {
            $this->assertSame(MoveDirection::EAST, $move->direction);
        }
    }

    public function testGetPossibleMovesWest(): void
    {
        $car = new Car(new Coordinate(3, 1), CarDirection::RIGHT);
        $board = new Board(4, 2);
        $board->addCar($car, 'r');
        $board->setExit(new Coordinate(0, 2));

        $player = new Player($board);

        $possibleMoves = iterator_to_array($player->getPossibleMoves());
        $this->assertCount(2, $possibleMoves);
        foreach ($possibleMoves as $move) {
            $this->assertSame(MoveDirection::WEST, $move->direction);
        }
    }

    public function testGetPossibleMovesWestToExit(): void
    {
        $car = new Car(new Coordinate(3, 1), CarDirection::RIGHT);
        $board = new Board(4, 1);
        $board->addCar($car, 'r');
        $board->setExit(new Coordinate(0, 1));

        $player = new Player($board);

        $possibleMoves = iterator_to_array($player->getPossibleMoves());
        $this->assertCount(3, $possibleMoves);
        foreach ($possibleMoves as $move) {
            $this->assertSame(MoveDirection::WEST, $move->direction);
        }
    }

    public function testGetPossibleMovesComplicatedBoard(): void
    {
        $boardDrawing =
            "@@@@@@@@\n" .
            "@kkk.e.@\n" .
            "@..j.e.@\n" .
            "@rrj.ea.\n" .
            "@ihhcca@\n" .
            "@iggd.b@\n" .
            "@fffd.b@\n" .
            "@@@@@@@@";
        $board = (new BoardDrawingParser())->boardFromDrawing($boardDrawing);

        $player = new Player($board);

        $possibleMoves = iterator_to_array($player->getPossibleMoves());
        $this->assertCount(3, $possibleMoves);
    }
}
