<?php

namespace Thisliu\Mixin\Middleware;

use Psr\Http\Message\RequestInterface;
use Thisliu\Mixin\Signature;

class CreateRequestSignature
{
    public function __construct() {
    }

    public function __invoke(callable $handler): \Closure
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            $request = $request->withHeader(
                'Authorization',
                (new Signature())->createAuthorizationHeader()
            );

            return $handler($request, $options);
        };
    }
}
