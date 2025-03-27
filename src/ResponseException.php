<?php

namespace Vliz\TemplatingClient;

use RuntimeException;
use Throwable;

/**
 * An exception requesting a specific HTTP response.  Call render() to create that response.
 */
class ResponseException extends RuntimeException
{

    /**
     * @param string $message A message, rendered  in text/plain
     * @param int $code The HTTP status code required
     * @param Throwable|null $previous As usual
     */
    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function render()
    {
        http_response_code($this->getCode());
        header('content-Type: text/plain; charset=UTF-8');
        echo $this->getMessage();
        die();
    }
}