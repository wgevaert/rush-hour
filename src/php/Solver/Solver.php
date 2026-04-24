<?php

namespace RushHour\Solver;

use LogicException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use RushHour\Models\Board;
use RushHour\Models\Move;
use RushHour\Hasher\BoardHasher;
use RushHour\Exception\UnsolvableException;

/**
 * A solver for the RushHour game
 *
 * Implements breadth-first search
 */
class Solver implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * Associative array of the form [ <hashed board> => <last move> ];
     * The starting board points to false
     *
     * @var array<string, Move|false> $states
     */
    private array $states;

    private BoardHasher $hasher;

    public function __construct(private readonly Board $startingBoard)
    {
    }

    public function setHasher(BoardHasher $hasher): void
    {
        $this->hasher = $hasher;
    }

    /**
     * Solves the puzzle
     *
     * @throws UnsolvableException if the board is unsolvable
     * @return iterable<Move> The moves to make from the original board to a solved board
     */
    public function solve(): iterable
    {
        $winningPlayer = $this->findMovesToSolvedState();
        return $this->traceBackMoves($winningPlayer);
    }

    private function findMovesToSolvedState(): Player
    {
        $this->registerBoard($this->startingBoard, false);
        $startingPlayer = $this->makeStartingPlayer();
        if ($startingPlayer->puzzleSolved()) {
            return $startingPlayer;
        }
        $players = [ $startingPlayer ];
        while (true) {
            if (empty($players)) {
                throw new UnsolvableException("Board is not solvable");
            }
            $newPlayers = [];
            foreach ($players as $player) {
                foreach ($this->doPossibleMoves($player) as $newPlayer) {
                    if ($newPlayer->puzzleSolved()) {
                        return $newPlayer;
                    }
                    $newPlayers[] = $newPlayer;
                }
            }
            $players = $newPlayers;
        }
    }

    private function makeStartingPlayer(): Player
    {
        $player = new Player(clone $this->startingBoard);
        if ($this->logger !== null) {
            $player->setLogger($this->logger);
        }
        return $player;
    }

    /**
     * @param Player $player The player that will make moves
     * @return iterable<Player> Players with board positions that have not been reached before
     */
    private function doPossibleMoves(Player $player): iterable
    {
        foreach ($player->getPossibleMoves() as $move) {
            $newPlayer = clone $player;
            $newPlayer->makeMove($move);
            if ($this->registerBoard($newPlayer->getBoard(), $move)) {
                $this->logger?->debug(
                    "Found a new position",
                    [
                        'oldBoard' => $player->getBoard(),
                        'move' => $move,
                        'newBoard' => $newPlayer->getBoard()
                    ]
                );
                yield $newPlayer;
            } else {
                $this->logger?->debug(
                    "Move rejected; position already exists",
                    [
                        'oldBoard' => $player->getBoard(),
                        'move' => $move,
                        'newBoard' => $newPlayer->getBoard()
                    ]
                );
            }
        }
    }

    private function registerBoard(Board $board, Move|false $move): bool
    {
        $hashedBoard = $this->hasher->hashBoard($board);
        if (isset($this->states[$hashedBoard])) {
            return false;
        }
        $this->states[$hashedBoard] = $move;
        return true;
    }

    /**
     * @param Player $player The player with a solved board
     * @return iterable<Move> The moves the player made to solve the board
     */
    private function traceBackMoves(Player $player): iterable
    {
        $moves = [];
        while ($this->hasPreviousMove($player)) {
            $moves [] = $this->doMoveBack($player);
        }
        return array_reverse($moves);
    }

    private function hasPreviousMove(Player $player): bool
    {
        $hashedBoard = $this->hasher->hashBoard($player->getBoard());
        return isset($this->states[ $hashedBoard ]) && $this->states[$hashedBoard] !== false;
    }

    private function doMoveBack(Player $player): Move
    {
        $hashedBoard = $this->hasher->hashBoard($player->getBoard());
        $move = $this->states[ $hashedBoard ];
        if ($move === false) {
            throw new LogicException("Tried to move back from starting board");
        }
        unset($this->states[$hashedBoard]);
        $player->makeMoveBackwards($move);

        return $move;
    }
}
