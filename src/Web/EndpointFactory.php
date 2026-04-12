<?php

namespace RushHour\Web;

use RushHour\Exception\UserErrorException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

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
            default => throw new UserErrorException('Unknown value provided for action'),
        };
    }
}
