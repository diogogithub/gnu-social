<?php

declare(strict_types = 1);

namespace Plugin\ImageEncoder\Exception;

use App\Util\Exception\ClientException;
use Throwable;

class UnsupportedFileTypeException extends ClientException
{
    public function __construct(string $message = '', int $code = 418, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
