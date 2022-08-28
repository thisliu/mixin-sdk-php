<?php

namespace Thisliu\Mixin\Exceptions;

use JetBrains\PhpStorm\Pure;
use Psr\Http\Message\ResponseInterface;
use Thisliu\Mixin\Http\Response;

class ClientException extends Exception
{
    protected \GuzzleHttp\Exception\ClientException $guzzleClientException;

    #[Pure]
    public function __construct(\GuzzleHttp\Exception\ClientException $guzzleServerException)
    {
        $this->guzzleClientException = $guzzleServerException;

        parent::__construct($guzzleServerException->getMessage(), $guzzleServerException->getCode(), $guzzleServerException->getPrevious());
    }

    public function getResponse(): ResponseInterface
    {
        return new Response($this->guzzleClientException->getResponse());
    }
}
