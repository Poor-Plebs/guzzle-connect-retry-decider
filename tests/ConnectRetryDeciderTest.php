<?php

declare(strict_types=1);

namespace PoorPlebs\GuzzleConnectRetryDecider\Tests;

use Closure;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use GuzzleHttp\RequestOptions;
use PHPUnit\Framework\TestCase;
use PoorPlebs\GuzzleConnectRetryDecider\ConnectRetryDecider;
use stdClass;

/**
 * @coversDefaultClass \PoorPlebs\GuzzleConnectRetryDecider\ConnectRetryDecider
 */
class ConnectRetryDeciderTest extends TestCase
{
    /**
     * @test
     * @covers \PoorPlebs\GuzzleConnectRetryDecider\ConnectRetryDecider
     */
    public function it_calls_on_before_retry_closure_before_retry(): void
    {
        $stream = Utils::streamFor('');
        $request = new Request(
            'GET',
            'https://sometest.com/information',
            [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'User-Agent' => 'GuzzleHttp/7',
            ],
            $stream,
        );

        $exception = new ConnectException(
            'cURL error 28: Operation timed out after 5001 milliseconds with 0 bytes received (see https://curl.haxx.se/libcurl/c/libcurl-errors.html) for https://sometest.com/information',
            $request,
            null,
        );

        $mockHttpHandler = new MockHandler([
            $exception,
            new Response(200, ['Content-Type' => 'application/json'], '{"ok":true}'),
        ]);

        $shouldBeCalled = $this->getMockBuilder(stdClass::class)
            ->addMethods(['__invoke'])
            ->getMock();

        $shouldBeCalled
            ->expects($this->once())
            ->method('__invoke')
            ->with(0, $request, $exception);

        $handlerStack = HandlerStack::create($mockHttpHandler);
        $handlerStack->push(
            Middleware::retry(new ConnectRetryDecider(onBeforeRetry: Closure::fromCallable($shouldBeCalled))),
            'connect_retry',
        );

        $client = new Client([
            'base_uri' => 'https://sometest.com/',
            'handler' => $handlerStack,
            RequestOptions::HEADERS => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);

        $client->getAsync(
            'information',
            [
                'body' => $stream,
            ]
        )->wait(true);
    }

    /**
     * @test
     * @covers \PoorPlebs\GuzzleConnectRetryDecider\ConnectRetryDecider
     */
    public function it_does_not_retry_post_if_http_connection_was_established(): void
    {
        $this->expectException(ConnectException::class);
        $this->expectExceptionMessage('Not retried');

        $mockHttpHandler = new MockHandler([
            new ConnectException(
                'Not retried',
                new Request('POST', 'information', ['Accept' => 'application/json', 'Content-Type' => 'application/json'], '{"some_data": "**********"}'),
                null,
                [
                    'http_code' => 0,
                    'connect_time' => 0.1,
                ],
            ),
            // Will never happen.
            new Response(200, ['Content-Type' => 'application/json'], '{"ok":true}'),
        ]);

        $handlerStack = HandlerStack::create($mockHttpHandler);
        $handlerStack->push(
            Middleware::retry(new ConnectRetryDecider()),
            'connect_retry',
        );

        $client = new Client([
            'base_uri' => 'http://sometest.com/',
            'handler' => $handlerStack,
        ]);

        $client->postAsync('information')->wait(true);
    }

    /**
     * @test
     * @covers \PoorPlebs\GuzzleConnectRetryDecider\ConnectRetryDecider
     */
    public function it_does_not_retry_post_if_https_connection_was_established(): void
    {
        $this->expectException(ConnectException::class);
        $this->expectExceptionMessage('Not retried');

        $mockHttpHandler = new MockHandler([
            new ConnectException(
                'Not retried',
                new Request('POST', 'information', ['Accept' => 'application/json', 'Content-Type' => 'application/json'], '{"some_data": "**********"}'),
                null,
                [
                    'http_code' => 0,
                    'connect_time' => 0.1,
                    'scheme' => 'HTTPS',
                    'appconnect_time' => 0.1,
                ],
            ),
            // Will never happen.
            new Response(200, ['Content-Type' => 'application/json'], '{"ok":true}'),
        ]);

        $handlerStack = HandlerStack::create($mockHttpHandler);
        $handlerStack->push(
            Middleware::retry(new ConnectRetryDecider()),
            'connect_retry',
        );

        $client = new Client([
            'base_uri' => 'https://sometest.com/',
            'handler' => $handlerStack,
        ]);

        $client->postAsync('information')->wait(true);
    }

    /**
     * @test
     * @covers \PoorPlebs\GuzzleConnectRetryDecider\ConnectRetryDecider
     */
    public function it_fails_after_all_retries(): void
    {
        $this->expectException(ConnectException::class);
        $this->expectExceptionMessage('Thrid retry');

        $mockHttpHandler = new MockHandler([
            new ConnectException(
                'Initial attempt',
                new Request('GET', 'information', ['Accept' => 'application/json', 'Content-Type' => 'application/json']),
            ),
            new ConnectException(
                'First retry',
                new Request('GET', 'information', ['Accept' => 'application/json', 'Content-Type' => 'application/json']),
            ),
            new ConnectException(
                'Second retry',
                new Request('GET', 'information', ['Accept' => 'application/json', 'Content-Type' => 'application/json']),
            ),
            new ConnectException(
                'Thrid retry',
                new Request('GET', 'information', ['Accept' => 'application/json', 'Content-Type' => 'application/json']),
            ),
            // Will never happen.
            new Response(200, ['Content-Type' => 'application/json'], '{"ok":true}'),
        ]);

        $handlerStack = HandlerStack::create($mockHttpHandler);
        $handlerStack->push(
            Middleware::retry(new ConnectRetryDecider(3)),
            'connect_retry',
        );

        $client = new Client([
            'base_uri' => 'https://sometest.com/',
            'handler' => $handlerStack,
        ]);

        $client->getAsync('information')->wait(true);
    }

    /**
     * @test
     * @covers \PoorPlebs\GuzzleConnectRetryDecider\ConnectRetryDecider
     */
    public function it_retries_on_get_connection_failures(): void
    {
        $mockHttpHandler = new MockHandler([
            new ConnectException(
                'cURL error 28: Operation timed out after 5001 milliseconds with 0 bytes received (see https://curl.haxx.se/libcurl/c/libcurl-errors.html) for https://sometest.com/information',
                new Request('GET', 'information', ['Accept' => 'application/json', 'Content-Type' => 'application/json']),
            ),
            new Response(200, ['Content-Type' => 'application/json'], '{"ok":true}'),
        ]);

        $handlerStack = HandlerStack::create($mockHttpHandler);
        $handlerStack->push(
            Middleware::retry(new ConnectRetryDecider()),
            'connect_retry',
        );

        $client = new Client([
            'base_uri' => 'https://sometest.com/',
            'handler' => $handlerStack,
        ]);

        /** @var \Psr\Http\Message\ResponseInterface $response */
        $response = $client->getAsync('information')->wait();

        $this->assertSame('{"ok":true}', (string)$response->getBody());
    }

    /**
     * @test
     * @covers \PoorPlebs\GuzzleConnectRetryDecider\ConnectRetryDecider
     */
    public function it_retries_on_post_connection_failures(): void
    {
        $mockHttpHandler = new MockHandler([
            new ConnectException(
                'cURL error 28: Operation timed out after 5001 milliseconds with 0 bytes received (see https://curl.haxx.se/libcurl/c/libcurl-errors.html) for https://sometest.com/information',
                new Request('POST', 'information', ['Accept' => 'application/json', 'Content-Type' => 'application/json'], '{"some_data": "**********"}'),
                null,
                [
                    'http_code' => 0,
                    'connect_time' => 0.1,
                    'scheme' => 'HTTPS',
                    'appconnect_time' => 0.0,
                ],
            ),
            new Response(200, ['Content-Type' => 'application/json'], '{"ok":true}'),
        ]);

        $handlerStack = HandlerStack::create($mockHttpHandler);
        $handlerStack->push(
            Middleware::retry(new ConnectRetryDecider()),
            'connect_retry',
        );

        $client = new Client([
            'base_uri' => 'https://sometest.com/',
            'handler' => $handlerStack,
        ]);

        /** @var \Psr\Http\Message\ResponseInterface $response */
        $response = $client->postAsync('information')->wait();

        $this->assertSame('{"ok":true}', (string)$response->getBody());
    }
}
