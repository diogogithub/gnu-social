<?php

namespace Plugin\ImageEncoder\Exception;

use App\Util\Exception\ClientException;
use Throwable;

class UnsupportedFileTypeException extends ClientException
{
    /**
     * @param string         $message
     * @param int            $code
     * @param null|Throwable $previous
     */
    public function __construct(string $message = '', int $code = 418, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}