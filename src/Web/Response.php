<?php

namespace RushHour\Web;

class Response
{
    public function __construct(
        private string $content = '',
        private int $statusCode = 200,
        private array $headers = [],
    ) {
    }

    public function setBody(string $content): void
    {
        $this->content = $content;
    }

    public function setCode(int $statusCode): void
    {
        $this->statusCode = $statusCode;
    }

    public function addHeader(string $header): void
    {
        $this->headers[] = $header;
    }

    public function getBody(): string
    {
        return $this->content;
    }

    public function getCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }
}
