<?php

namespace RushHour\Web;

use UnexpectedValueException;
use Psr\Log\LoggerAwareTrait;
use RushHour\Models\Board;
use RushHour\Services\BoardDrawer;
use RushHour\Services\BoardDrawingParser;
use RushHour\Services\BoardSerializer;
use RushHour\Services\CarPositionBoardSerializer;
use RushHour\Services\CarPositionBoardHasher;
use RushHour\Services\DrawingBoardSerializer;
use RushHour\Services\Solver;

abstract class BoardEndpoint implements ApiEndpoint
{
    use LoggerAwareTrait;

    public const SERIALIZATION_CARPOSITION = 'carpos';
    public const SERIALIZATION_DRAWING = 'draw';

    protected Board $board;

    public function setParameters(array $params): void
    {
        if (!isset($params['board'])) {
            throw new UnexpectedValueException("parameter board is required for this endpoint");
        }
        $board = $params['board'];

        $serialization = $params['serialization'] ?? self::SERIALIZATION_CARPOSITION;
        $serializer = $this->getBoardSerializer($serialization);
        $this->board = $serializer->unserializeBoard($board);
    }

    abstract public function execute(): array;

    public function getBoard(): Board
    {
        return $this->board;
    }

    protected function getBoardSerializer(string $serialization): BoardSerializer
    {
        return match ($serialization) {
            self::SERIALIZATION_CARPOSITION => new CarPositionBoardSerializer(),
            self::SERIALIZATION_DRAWING => new DrawingBoardSerializer(new BoardDrawer, new BoardDrawingParser),
            default => throw new UnexpectedValueException("Unknown serialization"),
        };
    }
}
