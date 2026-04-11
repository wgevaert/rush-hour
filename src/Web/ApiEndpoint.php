<?php

namespace RushHour\Web;

use Psr\Log\LoggerAwareInterface;
use RuntimeException;
use Throwable;

interface ApiEndpoint extends LoggerAwareInterface
{
    /**
     * Extracts required parameters for this endpoint from $params and validates them.
     *
     * @param array<string, mixed> $params The parameters that the endpoint should use to execute its action.
     */
    public function setParameters(array $params): void;

    /**
     * Executes the endpoint
     *
     * @throws RuntimeException If the endpoint could not complete due to invalid input
     * @throws Throwable If any other issues occur
     *
     * @return array<string, mixed> A response that can be jsonserialized
     */
    public function execute(): array;
}
