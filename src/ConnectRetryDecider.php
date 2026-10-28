<?php

declare(strict_types=1);

namespace PoorPlebs\GuzzleConnectRetryDecider;

use Closure;
use GuzzleHttp\Exception\ConnectException;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

final class ConnectRetryDecider
{
    private const DEFAULT_MAX_RETRY = 3;

    /**
     * @param (Closure(int,RequestInterface,Throwable):void)|null $onBeforeRetry
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
        mixed $exception
    ): bool {
        /* Guzzle 6 connect exceptions do not implement the PSR-18 interface,
         * Guzzle 8 adds network exceptions besides connect exceptions.
         */
        if (!$exception instanceof ConnectException && !$exception instanceof NetworkExceptionInterface) {
            return false;
        }

        if ($retries >= $this->maxRetries) {
            return false;
        }

        if ($request->getMethod() === 'GET' || $this->connectionNeverEstablished($exception)) {
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

    private function connectionNeverEstablished(Throwable $exception): bool
    {
        if (!$exception instanceof ConnectException) {
            return false;
        }

        /* Guzzle 8 throws connect exceptions only while establishing the
         * connection and dropped the handler context.
         */
        // @phpstan-ignore-next-line Always true when analysed against Guzzle 6 or 7.
        if (!method_exists($exception, 'getHandlerContext')) {
            return true;
        }

        /** @var array<string,mixed> $handlerContext */
        $handlerContext = $exception->getHandlerContext();

        return ($handlerContext['http_code'] ?? null) === 0 &&
            (
                ($handlerContext['connect_time'] ?? null) === 0.0 ||
                (
                    ($handlerContext['scheme'] ?? null) === 'HTTPS' &&
                    ($handlerContext['appconnect_time'] ?? null) === 0.0
                )
            );
    }
}
