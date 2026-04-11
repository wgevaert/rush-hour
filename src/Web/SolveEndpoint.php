<?php

namespace RushHour\Web;

use RushHour\Exception\UnsolvableException;
use RushHour\Models\Board;
use RushHour\Models\Move;
use RushHour\Services\CarPositionBoardHasher;
use RushHour\Services\MoveSerializer;
use RushHour\Services\Solver;

class SolveEndpoint extends BoardEndpoint
{
    public function execute(): array
    {
        $solver = new Solver($this->board);
        $solver->setHasher(new CarPositionBoardHasher());
        try {
            $solution = $solver->solve();
            return [
                'solved' => true,
                'moves' => $this->serializeSolution($solution),
            ];
        } catch (UnsolvableException $e) {
            return [
                'solved' => false,
                'reason' => $e->getMessage(),
            ];
        }
    }

    /**
     * @param iterable<Move> $solution
     * @return array<string> Array of serialized moves
     */
    private function serializeSolution(iterable $solution): array
    {
        $moveSerializer = new MoveSerializer();
        return array_map(
            $moveSerializer->serializeMove(...),
            iterator_to_array($solution)
        );
    }
}
