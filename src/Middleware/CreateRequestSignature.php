<?php

namespace Thisliu\Mixin\Middleware;

use Psr\Http\Message\RequestInterface;
use Thisliu\Mixin\Config;
use Thisliu\Mixin\Signature;

class CreateRequestSignature
{
    public function __construct(public Config $config, string $type) {
    }

    public function __invoke(callable $handler): \Closure
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            $request = $request->withHeader(
                'Authorization',
                (new Signature($this->config))->createAuthorizationHeader($request)
            );

            return $handler($request, $options);
        };
    }
}
