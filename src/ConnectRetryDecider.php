<?php

declare(strict_types=1);

namespace PoorPlebs\GuzzleConnectRetryDecider;

use Closure;
use GuzzleHttp\Exception\ConnectException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

final class ConnectRetryDecider
{
    private const DEFAULT_MAX_RETRY = 3;

    /**
     * @param (Closure(int,\Psr\Http\Message\RequestInterface,\Throwable):void)|null $onBeforeRetry
     */
    public function __construct(
        private readonly int $maxRetries = self::DEFAULT_MAX_RETRY,
        private readonly Closure|null $onBeforeRetry = null
    ) {
    }

    public function __invoke(
        int $retries,
        RequestInterface $request,
        ResponseInterface|null $response,
        Throwable|null $exception
    ): bool {
        if (!$exception instanceof ConnectException) {
            return false;
        }

        if ($retries >= $this->maxRetries) {
            return false;
        }

        if ($request->getMethod() === 'GET') {
            $this->callOnBeforeRetry($retries, $request, $exception);

            return true;
        }

        $handlerContext = $exception->getHandlerContext();
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
            $this->callOnBeforeRetry($retries, $request, $exception);

            return true;
        }

        return false;
    }

    private function callOnBeforeRetry(
        int $retries,
        RequestInterface $request,
        Throwable $exception,
    ): void {
        if (is_callable($this->onBeforeRetry)) {
            ($this->onBeforeRetry)($retries, $request, $exception);
        }
    }
}
