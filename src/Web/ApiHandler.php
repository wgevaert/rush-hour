<?php

namespace RushHour\Web;

use Throwable;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use RushHour\Exception\UserErrorException;

class ApiHandler implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private ApiEndpoint $endpoint;
    private Response $response;

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
    public function handleRequest(): Response
    {
        try {
            $this->initResponse();
            $this->initEndpoint();
            $body = $this->endpoint->execute();
            $this->response->setBody(json_encode($body));
        } catch (Throwable $throwable) {
            $this->handleException($throwable);
        }
        return $this->response;
    }

    private function initResponse(): void
    {
        $this->response = new Response();
        $this->response->addHeader('Content-Type: application/json');
    }

    private function initEndpoint(): void
    {
        $factory = new EndpointFactory();
        if ($this->logger !== null) {
            $factory->setLogger($this->logger);
        }
        $this->endpoint = $factory->getEndpoint($this->params);
        $this->endpoint->setParameters($this->params);
    }

    /**
     * @param Throwable $throwable Exception to render to user
     * @return array<mixed>
     */
    private function handleException(Throwable $throwable): void
    {
        $this->logException($throwable);
        if ($throwable instanceof UserErrorException) {
            $this->handleUserError($throwable);
        } else {
            $this->handleUnknownError($throwable);
        }
    }

    /**
     * @param Throwable $throwable Exception to render to user
     */
    private function handleUserError(Throwable $throwable): void
    {
        $httpResponseCode = $throwable->getCode();
        if ($httpResponseCode < 400 || $httpResponseCode > 499) {
            $httpResponseCode = 400;
        }
        $this->response->setCode($httpResponseCode);

        $this->setErrorBody($throwable->getMessage(), $throwable->getCode());
    }

    /**
     * @param Throwable $throwable Exception to render to user
     */
    private function handleUnknownError(Throwable $throwable): void
    {
        $this->response->setCode(500);
        $this->setErrorBody('An unexpected error occurred, see the logs for details', $throwable->getCode());
    }

    private function setErrorBody(string $message, int $code): void
    {
        $this->response->setBody(json_encode([
            'error' => [
                'message' => $message,
                'code' => $code,
            ],
        ]));
    }

    private function logException(Throwable $exception): void
    {
        $exceptionStrings = [];
        do {
            $exceptionStrings[] = $exception->__toString();
            $exception = $exception->getPrevious();
        } while ($exception !== null);

        $this->logger?->error(implode("\nPrevious:", $exceptionStrings));
    }
}
