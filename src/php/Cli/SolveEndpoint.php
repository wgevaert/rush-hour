<?php

namespace RushHour\Cli;

use Psr\Log\LoggerAwareTrait;
use RushHour\Exception\UnsolvableException;
use RushHour\Exception\UserErrorException;
use RushHour\Models\Board;
use RushHour\Models\Move;
use RushHour\Hasher\HasherFactory;
use RushHour\Solver\Player;
use RushHour\Solver\Solver;
use RushHour\Serialization\BoardDrawingParser;
use RushHour\Serialization\BoardDrawer;
use RushHour\Serialization\MoveSerializer;

class SolveEndpoint implements CommandLineEndpoint
{
    use LoggerAwareTrait;

    private CommandLineInputOutput $io;
    private array $args = [];
    private array $options = [];

    public function setIO(CommandLineInputOutput $io): void
    {
        $this->io = $io;
    }

    public function setArgs(array $args): void
    {
        $this->args = $args;
    }

    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    public function run(): void
    {
        $board = $this->readBoard();
        $solver = new Solver($board);
        $solver->setHasher(HasherFactory::getBestHasher($board));
        if ($this->logger !== null) {
            $solver->setLogger($this->logger);
        }
        try {
            $solution = $solver->solve();
            $this->visualizeSolution($solution, $board);
        } catch (UnsolvableException $e) {
            $this->io->writeLine("Board is unsolvable: " . $e->getMessage());
        }
    }

    private function readBoard(): Board
    {
        $lines = [];
        while (true) {
            $line = $this->io->readLine();
            if ($line === null) {
                break;
            }
            $lines[] = $line;
        }
        $boardString = trim(implode("", $lines));
        $this->io->writeLine("Input:");
        $this->io->writeLine($boardString);
        return (new BoardDrawingParser())->boardFromDrawing($boardString);
    }

    private function visualizeSolution(iterable $solution, Board $initialBoard): void
    {
        $boardDrawer = new BoardDrawer();
        $moveSerializer = new MoveSerializer();
        $player = new Player($initialBoard);

        $this->io->writeLine("\nInitial board:");
        $this->io->writeLine($boardDrawer->draw($initialBoard));

        foreach ($solution as $move) {
            $player->makeMove($move);
            $boardString = $boardDrawer->draw($player->getBoard());

            $this->io->writeLine("\nMove: " . $moveSerializer->serializeMove($move));
            $this->io->writeLine($boardString);
        }
        $this->io->writeLine("\nPuzzle solved!!");
    }
}
