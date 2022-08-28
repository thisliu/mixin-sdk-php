<?php

namespace Thisliu\Mixin;

use Thisliu\Mixin\Exceptions\ClientException;
use Thisliu\Mixin\Exceptions\Exception;
use Thisliu\Mixin\Exceptions\ServerException;
use Thisliu\Mixin\Http\Response;
use Thisliu\Mixin\Traits\HttpClient;

/**
 * @method \Thisliu\Mixin\Http\Response get($uri, array $options = [])
 * @method \Thisliu\Mixin\Http\Response head($uri, array $options = [])
 * @method \Thisliu\Mixin\Http\Response options($uri, array $options = [])
 * @method \Thisliu\Mixin\Http\Response put($uri, array $options = [])
 * @method \Thisliu\Mixin\Http\Response post($uri, array $options = [])
 * @method \Thisliu\Mixin\Http\Response patch($uri, array $options = [])
 * @method \Thisliu\Mixin\Http\Response delete($uri, array $options = [])
 * @method \Thisliu\Mixin\Http\Response request(string $method, $uri, array $options = [])
 * @method \GuzzleHttp\Promise\PromiseInterface getAsync($uri, array $options = [])
 * @method \GuzzleHttp\Promise\PromiseInterface headAsync($uri, array $options = [])
 * @method \GuzzleHttp\Promise\PromiseInterface optionsAsync($uri, array $options = [])
 * @method \GuzzleHttp\Promise\PromiseInterface putAsync($uri, array $options = [])
 * @method \GuzzleHttp\Promise\PromiseInterface postAsync($uri, array $options = [])
 * @method \GuzzleHttp\Promise\PromiseInterface patchAsync($uri, array $options = [])
 * @method \GuzzleHttp\Promise\PromiseInterface deleteAsync($uri, array $options = [])
 * @method \GuzzleHttp\Promise\PromiseInterface requestAsync(string $method, $uri, array $options = [])
 */
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
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    public function setConfig(Config $config): Config
    {
        $this->config = $config;

        return $config;
    }

    public function getHttpClient(): \GuzzleHttp\Client
    {
        return $this->client ?? $this->client = $this->createHttpClient();
    }

    /**
     * @throws ServerException
     * @throws Exception
     * @throws ClientException
     */
    public function __call($method, $arguments)
    {
        try {
            return new Response(\call_user_func_array([$this->getHttpClient(), $method], $arguments));
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            throw new ClientException($e);
        } catch (\GuzzleHttp\Exception\ServerException $e) {
            throw new ServerException($e);
        } catch (\Throwable $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e->getPrevious());
        }
    }
}
