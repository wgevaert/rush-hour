<?php

namespace RushHour\Web;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use RushHour\Exception\UserErrorException;
use RushHour\Serialization\BoardDrawer;
use RushHour\Serialization\BoardDrawingParser;
use RushHour\Serialization\DrawingBoardSerializer;
use RushHour\Storage\SerializerStorage;
use RushHour\Storage\Storage;

class EndpointFactory implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @param array<string, mixed> $params
     * @return ApiEndpoint The endpoint corresponding to params['action']
     */
    public function getEndpoint(array $params): ApiEndpoint
    {
        if (!isset($params['action'])) {
            throw new UserErrorException('Missing parameter action');
        }
        if (!is_string($params['action'])) {
            throw new UserErrorException('Parameter action should be string');
        }
        $action = $params['action'];
        $endpoint = $this->makeEndpointForAction($action);
        if ($this->logger !== null) {
            $endpoint->setLogger($this->logger);
        }
        return $endpoint;
    }

    private function makeEndpointForAction(string $action): ApiEndpoint
    {
        return match ($action) {
            'solve' => new SolveEndpoint(),
            'draw' => new DrawEndpoint(),
            'storeBoard' => $this->getStoreEndpoint(),
            'fetchBoard' => $this->getFetchEndpoint(),
            default => throw new UserErrorException('Unknown value provided for action'),
        };
    }

    private function getStoreEndpoint(): StoreEndpoint
    {
        $endpoint = new StoreEndpoint();
        $endpoint->setStorage($this->getStorage());
        return $endpoint;
    }

    private function getFetchEndpoint(): FetchEndpoint
    {
        $endpoint = new FetchEndpoint();
        $endpoint->setStorage($this->getStorage());
        return $endpoint;
    }

    private function getStorage(): Storage
    {
        $serializer = new DrawingBoardSerializer(new BoardDrawer(), new BoardDrawingParser());
        $storage = new SerializerStorage();
        $storage->setSerializer($serializer);
        return $storage;
    }
}
