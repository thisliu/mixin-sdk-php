<?php

namespace Thisliu\Mixin;

use Thisliu\Mixin\Middleware\CreateRequestSignature;
use Thisliu\Mixin\Support\Signer;

class Mixin extends Client
{
    /**
     * @throws \Thisliu\Mixin\Exceptions\InvalidArgumentException
     */
    public function __construct(protected array|Config $config, string $type = Signer::TYPE_PRIVATE)
    {
        Signer::verifyType($type);

        parent::__construct($this->config);

        $this->pushMiddleware(
            new CreateRequestSignature($this->config, $type)
        );
    }
}
