<?php

namespace RushHour\Web;

use UnexpectedValueException;
use InvalidArgumentException;
use RuntimeException;
use Throwable;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class ApiHandler implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private ApiEndpoint $endpoint;

    /** @var array<string, mixed> $params */
    private array $params;

    /**
     * @param array<mixed> $params Request parameters
     */
    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    /**
     * @return array<mixed>
     */
    public function handleRequest(): array
    {
        try {
            $this->initEndpoint();
            return $this->endpoint->execute();
        } catch (Throwable $throwable) {
            return $this->handleException($throwable);
        }
    }

    private function initEndpoint(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $params = match ($method) {
            'POST' => $_POST,
            'GET' => $_GET,
            default => throw new UnexpectedValueException('Invalid method', 405),
        };
        $this->endpoint = (new EndpointFactory())->getEndpoint($this->params);
        $this->endpoint->setParameters($this->params);
    }

    /**
     * @param Throwable $throwable Exception to render to user
     * @return array<mixed>
     */
    private function handleException(Throwable $throwable): array
    {
        $this->logException($throwable);
        if ($throwable instanceof RuntimeException) {
            return $this->handleUserError($throwable);
        }
        return $this->handleUnknownError($throwable);
    }

    /**
     * @param Throwable $throwable Exception to render to user
     * @return array<mixed>
     */
    private function handleUserError(Throwable $throwable): array
    {
        $httpResponseCode = $throwable->getCode();
        if ($httpResponseCode < 400 || $httpResponseCode > 499) {
            $httpResponseCode = 400;
        }
        http_response_code($httpResponseCode);

        return [
            'error' => $throwable->getMessage(),
            'code' => $throwable->getCode(),
        ];
    }

    /**
     * @param Throwable $throwable Exception to render to user
     * @return array<mixed>
     */
    private function handleUnknownError(Throwable $throwable): array
    {
        http_response_code(500);
        return [
            'error' => 'An unexpected error occurred, see the logs for details',
            'code' => $throwable->getCode(),
        ];
    }

    private function logException(Throwable $exception): void
    {
        $exceptionString = '';
        do {
            $exceptionString .= $exception->__toString();
            $exception = $exception->getPrevious();
        } while ($exception !== null);

        $this->logger?->error($exceptionString);
    }
}
