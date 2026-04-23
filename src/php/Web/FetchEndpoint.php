<?php

namespace RushHour\Web;

use Psr\Log\LoggerAwareTrait;
use RushHour\Exception\UserErrorException;
use RushHour\Models\Board;
use RushHour\Storage\Storage;
use RushHour\Serialization\CarPositionBoardSerializer;

class FetchEndpoint implements ApiEndpoint
{
    use LoggerAwareTrait;

    private Storage $storage;

    private string $id;

    public function setParameters(array $params): void
    {
        if (!isset($params['id'])) {
            throw new UserErrorException("Parameter 'id' required to fetch board");
        }
        $this->id = $params['id'];
    }

    public function setStorage(Storage $storage)
    {
        $this->storage = $storage;
    }

    public function execute(): array
    {
        $board = $this->storage->fetchBoard($this->id);
        if ($board instanceof Board) {
            $serializer = new CarPositionBoardSerializer();
            return [
                'status' => 'success',
                'board' => $serializer->serializeBoard($board),
            ];
        }
        throw new UserErrorException("No board found with this ID", 404);
    }
}
