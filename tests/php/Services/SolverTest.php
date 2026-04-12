<?php

namespace RushHour\Test\Services;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RushHour\Models\Board;
use RushHour\Models\Car;
use RushHour\Models\CarDirection;
use RushHour\Models\Coordinate;
use RushHour\Models\Move;
use RushHour\Models\MoveDirection;
use RushHour\Exception\UnsolvableException;
use RushHour\Serialization\BoardDrawingParser;
use RushHour\Services\CarPositionBoardHasher;
use RushHour\Services\Player;
use RushHour\Services\Solver;

class SolverTest extends TestCase
{
    #[DataProvider('solveProvider')]
    public function testSolve(string $boardDrawing, int $expectedSolutionCount): void
    {
        $board = $this->boardFromDrawing($boardDrawing);
        $solver = $this->getSolver($board);
        $solution = $solver->solve();

        $this->assertCount($expectedSolutionCount, $solution);
        $this->assertIsASolution($solution, $board);
    }

    /**
     * @return list<list{string,int}>
     */
    public static function solveProvider(): array
    {
        return [
            [
                "@@@.@@@\n" .
                "@.....@\n" .
                "@.....@\n" .
                "@..r..@\n" .
                "@..r..@\n" .
                "@.....@\n" .
                "@.....@\n" .
                "@@@@@@@",
                1
            ],
            [
                "@.@@@@@\n" .
                "@bb...@\n" .
                "@.....@\n" .
                "@r....@\n" .
                "@r....@\n" .
                "@.....@\n" .
                "@.....@\n" .
                "@@@@@@@",
                2
            ],
            [
                "@@@@@@@@\n" .
                "@kkk.e.@\n" .
                "@..j.e.@\n" .
                "@rrj.ea.\n" .
                "@.hh...@\n" .
                "@......@\n" .
                "@......@\n" .
                "@@@@@@@@",
                5
            ],
            [
                "@@@@@@@@\n" .
                "@kkk.e.@\n" .
                "@..j.e.@\n" .
                "@rrj.ea.\n" .
                "@ihhcca@\n" .
                "@iggd.b@\n" .
                "@fffd.b@\n" .
                "@@@@@@@@",
                31
            ],
        ];
    }

    /**
     * Tests if the moves in $solution actually solve the puzzle from $board
     *
     * @param iterable<Move> $solution
     * @param Board $board
     */
    private function assertIsASolution(iterable $solution, Board $board): void
    {
        $player = new Player($board);
        foreach ($solution as $move) {
            $this->assertFalse($player->puzzleSolved(), 'Board is solved too early');
            $player->makeMove($move);
        }

        $this->assertTrue($player->puzzleSolved(), 'Board is not solved by solution');
    }

    private function getSolver(Board $board): Solver
    {
        $solver = new Solver($board);
        $solver->setHasher(new CarPositionBoardHasher());
        return $solver;
    }

    private function boardFromDrawing(string $boardDrawing): Board
    {
        return (new BoardDrawingParser())->boardFromDrawing($boardDrawing);
    }
}
