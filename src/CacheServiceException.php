<?php

namespace Kbra\Cache;

use Exception;
use Throwable;

class CacheServiceException extends Exception
{
    const MSG_CONNECTION_FAILED = "Could not connect to %s after %d tries";

    /**
     * @param string $cacheDriver
     * @param int $numberOfTries
     * @param Throwable $previousException
     * @return CacheServiceException
     */
    public static function connectionFailed($cacheDriver, $numberOfTries, $previousException = null) {
        $message = sprintf(self::MSG_CONNECTION_FAILED, $cacheDriver, $numberOfTries);

        return new static($message, 666, $previousException);
    }
}
