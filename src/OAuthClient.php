<?php

namespace Thisliu\Mixin;

use Thisliu\Mixin\Middleware\CreateRequestSignature;
use Thisliu\Mixin\Support\Signer;

class OAuthClient extends Client
{
    public function __construct(protected array|Config $config)
    {
        parent::__construct($this->config);

        $this->pushMiddleware(
            new CreateRequestSignature($this->config, Signer::TYPE_OAUTH)
        );
    }
}
