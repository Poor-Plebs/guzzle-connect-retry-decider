<?php

declare(strict_types=1);

namespace PoorPlebs\GuzzleConnectRetryDecider;

use GuzzleHttp\Exception\ConnectException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

final class ConnectRetryDecider
{
    private const DEFAULT_MAX_RETRY = 3;

    public function __construct(private readonly int $maxRetries = self::DEFAULT_MAX_RETRY)
    {
    }

    public function __invoke(int $retries, RequestInterface $req, ?ResponseInterface $res, ?Throwable $exc): bool
    {
        if (!$exc instanceof ConnectException) {
            return false;
        }

        if ($retries >= $this->maxRetries) {
            return false;
        }

        if ($req->getMethod() === 'GET') {
            return true;
        }

        $handlerContext = $exc->getHandlerContext();
        if (
            $handlerContext['http_code'] === 0 &&
            (
                $handlerContext['connect_time'] === 0.0 ||
                (
                    array_key_exists('scheme', $handlerContext) &&
                    $handlerContext['scheme'] === 'HTTPS' &&
                    $handlerContext['appconnect_time'] === 0.0
                )
            )
        ) {
            return true;
        }

        return false;
    }
}
