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
    private string $fetchBy = 'id';

    public function setParameters(array $params): void
    {
        if (isset($params['fetchBy'])) {
            $this->fetchBy = $params['fetchBy'];
        }
        if ($this->fetchBy === 'id') {
            if (!isset($params['id'])) {
                throw new UserErrorException("Parameter 'id' required when fetching board by ID");
            }
            $this->id = $params['id'];
        }
    }

    public function setStorage(Storage $storage)
    {
        $this->storage = $storage;
    }

    public function execute(): array
    {
        if ($this->fetchBy !== 'id') {
            throw new UserErrorException("Unimplemented fetch board method");
        }
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
