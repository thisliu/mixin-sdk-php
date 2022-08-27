<?php

namespace Thisliu\Mixin;

use Thisliu\Mixin\Middleware\CreateRequestSignature;
use Thisliu\Mixin\Traits\HttpClient;

class Client
{
    use HttpClient;

    protected \GuzzleHttp\Client $client;

    public function __construct(protected array|Config $config)
    {
        if (!($config instanceof Config)) {
            $config = new Config($config);
        }

        $this->config = $config;

        $this->pushMiddleware(
            new CreateRequestSignature()
        );
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    public function getHttpClient(): \GuzzleHttp\Client
    {
        return $this->client ?? $this->client = $this->createHttpClient();
    }
}
