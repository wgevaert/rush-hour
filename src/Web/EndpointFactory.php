<?php

namespace RushHour\Web;

use UnexpectedValueException;

class EndpointFactory
{
    /**
     * @param array<string, mixed> $params
     * @return ApiEndpoint The endpoint corresponding to params['action']
     */
    public function getEndpoint(array $params): ApiEndpoint
    {
        if (!isset($params['action'])) {
            throw new UnexpectedValueException('Missing parameter action');
        }
        if (!is_string($params['action'])) {
            throw new UnexpectedValueException('Parameter action should be string');
        }
        $action = $params['action'];
        $endpoint = $this->makeEndpointForAction($action);
        return $endpoint;
    }

    private function makeEndpointForAction(string $action): ApiEndpoint
    {
        return match ($action) {
            'solve' => new SolveEndpoint(),
            'draw' => new DrawEndpoint(),
            default => throw new UnexpectedValueException('unknown action'),
        };
    }
}
