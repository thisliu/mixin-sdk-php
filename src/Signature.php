<?php

namespace Thisliu\Mixin;

use Psr\Http\Message\RequestInterface;
use Thisliu\Mixin\Support\Signer;

class Signature
{
    public function __construct(public Config $config)
    {
    }

    /**
     * @throws \Thisliu\Mixin\Exceptions\LoadPrivateKeyException
     * @throws \Thisliu\Mixin\Exceptions\InvalidArgumentException
     * @throws \SodiumException
     */
    public function createAuthorizationHeader(RequestInterface $request, int $expires = 200): string
    {
        return Signer::build($request, Signer::TYPE_PRIVATE, $this->config->extend(['expires' => $expires]));
    }
}
