<?php

namespace Thisliu\Mixin\Middleware;

use Psr\Http\Message\RequestInterface;

class SetBodyPin
{
    public function __invoke(callable $handler): \Closure
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            $request = $request->withBody(
                $request->getBody()
            );

            return $handler($request, $options);
        };
    }
}
