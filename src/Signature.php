<?php

namespace Thisliu\Mixin;

class Signature
{
    public const BASE = 'base';
    public const OAUTH = 'oauth';
    public const PIN = 'pin';

    public function createAuthorizationHeader(): string
    {
        return '';
    }
}
