<?php
declare(strict_types=1);

namespace LessHttpTest\Cors;

use LessHttp\Cors\CorsMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @covers \LessHttp\Cors\CorsMiddleware
 */
final class CorsMiddlewareTest extends TestCase
{
    public function testOptions(): void
    {
        $response = $this->createMock(ResponseInterface::class);

        $response
            ->expects(self::exactly(4))
            ->method('withHeader')
            ->withConsecutive(
                ['access-control-allow-methods', 'post,get'],
                ['access-control-allow-headers', 'foo,bar'],
                ['access-control-allow-origin', 'https://foo.bar'],
                ['access-control-max-age', '123'],
            )
            ->willReturn($response);

        $responseFactory = $this->createMock(ResponseFactoryInterface::class);

        $responseFactory
            ->expects(self::once())
            ->method('createResponse')
            ->with(204)
            ->willReturn($response);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->expects(self::never())
            ->method('handle');

        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->expects(self::once())
            ->method('getMethod')
            ->willReturn('OPTIONS');

        $request
            ->method('getHeaderLine')
            ->willReturnMap(
                [
                    ['access-control-request-method', 'post'],
                    ['access-control-request-headers', 'foo'],
                    ['origin', 'https://foo.bar'],
                ],
            );

        $middleware = new CorsMiddleware(
            $responseFactory,
            ['https://foo.bar'],
            ['post', 'get'],
            ['foo', 'bar'],
            123
        );

        self::assertSame($response, $middleware->process($request, $handler));
    }

    public function testPost(): void
    {
        $response = $this->createMock(ResponseInterface::class);

        $response
            ->expects(self::exactly(2))
            ->method('withHeader')
            ->withConsecutive(
                ['access-control-allow-origin', 'https://foo.bar'],
                ['access-control-max-age', '123'],
            )
            ->willReturn($response);

        $responseFactory = $this->createMock(ResponseFactoryInterface::class);

        $responseFactory
            ->expects(self::never())
            ->method('createResponse');

        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->expects(self::once())
            ->method('getMethod')
            ->willReturn('POST');

        $request
            ->method('getHeaderLine')
            ->willReturnMap(
                [
                    ['access-control-request-method', 'post'],
                    ['access-control-request-headers', 'foo'],
                    ['origin', 'https://foo.bar'],
                ],
            );

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->expects(self::once())
            ->method('handle')
            ->with($request)
            ->willReturn($response);

        $middleware = new CorsMiddleware(
            $responseFactory,
            ['https://foo.bar'],
            ['post', 'get'],
            ['foo', 'bar'],
            123
        );

        self::assertSame($response, $middleware->process($request, $handler));
    }
}
