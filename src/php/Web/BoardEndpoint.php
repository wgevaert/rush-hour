<?php

namespace RushHour\Web;

use Psr\Log\LoggerAwareTrait;
use RushHour\Exception\UserErrorException;
use RushHour\Models\Board;
use RushHour\Serialization\BoardDrawer;
use RushHour\Serialization\BoardDrawingParser;
use RushHour\Serialization\BoardSerializer;
use RushHour\Serialization\CarPositionBoardSerializer;
use RushHour\Solver\CarPositionBoardHasher;
use RushHour\Serialization\DrawingBoardSerializer;

abstract class BoardEndpoint implements ApiEndpoint
{
    use LoggerAwareTrait;

    public const SERIALIZATION_CARPOSITION = 'carpos';
    public const SERIALIZATION_DRAWING = 'draw';

    protected Board $board;

    public function setParameters(array $params): void
    {
        if (!isset($params['board'])) {
            throw new UserErrorException("parameter board is required for this endpoint");
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
            self::SERIALIZATION_DRAWING => new DrawingBoardSerializer(new BoardDrawer(), new BoardDrawingParser()),
            default => throw new UserErrorException("Unknown serialization"),
        };
    }
}
