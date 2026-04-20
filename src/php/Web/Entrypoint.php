<?php

namespace RushHour\Web;

use RushHour\Exception\UserErrorException;
use RushHour\Logger\FileLogger;
use RushHour\Models\Config;
use RuntimeException;

class Entrypoint
{
    private array $server = [];
    private array $post = [];
    private array $get = [];
    private ?Config $config = null;

    public function setServer(array $server): void
    {
        $this->server = $server;
    }

    public function setPost(array $post): void
    {
        $this->post = $post;
    }

    public function setGet(array $get): void
    {
        $this->get = $get;
    }

    public function setConfig(Config $config): void
    {
        $this->config = $config;
    }

    public function run(): Response
    {
        $response = $this->getApiHandler()->handleRequest();
        $this->addGeneralHeaders($response);
        return $response;
    }

    private function getApiHandler(): ApiHandler
    {
        $handler = new ApiHandler();
        $handler->setParams($this->getParams());
        $handler->setLogger($this->getLogger());
        return $handler;
    }

    private function addGeneralHeaders(Response $response): void
    {
        $regexes = $this->getConfig()->get("cors-origins-regexps");
        if (!is_array($regexes)) {
            throw new RuntimeException("Invalid config value for cors-origins-regexps, expected array");
        }
        foreach ($regexes as $regex) {
            if (!is_string($regex)) {
                throw new RuntimeException("Invalid config value for cors-origins-regexps, expected array of regexes");
            }
            if (isset($this->server['HTTP_ORIGIN']) && preg_match($regex, $this->server['HTTP_ORIGIN'])) {
                $response->addHeader('Access-Control-Allow-Origin: ' . $this->server['HTTP_ORIGIN']);
                break;
            }
        }
    }

    private function getLogger(): FileLogger
    {
        return new FileLogger();
    }

    private function getConfig(): Config
    {
        if ($this->config === null) {
            $this->config = new Config();
        }
        return $this->config;
    }

    private function getParams(): array
    {
        $method = $this->server['REQUEST_METHOD'];

        return match ($method) {
            'POST' => $this->post,
            'GET' => $this->get,
            default => [],
        };
    }
}
