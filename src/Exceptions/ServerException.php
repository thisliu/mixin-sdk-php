<?php

namespace Thisliu\Mixin\Exceptions;

use JetBrains\PhpStorm\Pure;
use Psr\Http\Message\ResponseInterface;
use Thisliu\Mixin\Http\Response;

class ServerException extends Exception
{
    protected \GuzzleHttp\Exception\ServerException $guzzleServerException;

    #[Pure]
    public function __construct(\GuzzleHttp\Exception\ServerException $guzzleServerException)
    {
        $this->guzzleServerException = $guzzleServerException;

        parent::__construct($guzzleServerException->getMessage(), $guzzleServerException->getCode(), $guzzleServerException->getPrevious());
    }

    public function getResponse(): ResponseInterface
    {
        return new Response($this->guzzleServerException->getResponse());
    }
}
