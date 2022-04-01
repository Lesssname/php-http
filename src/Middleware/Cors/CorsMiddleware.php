<?php
declare(strict_types=1);

namespace LessHttp\Middleware\Cors;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class CorsMiddleware implements MiddlewareInterface
{
    /**
     * @param ResponseFactoryInterface $responseFactory
     * @param array<string> $origins
     * @param array<string> $methods
     * @param array<string> $headers
     * @param int $maxAge
     */
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly array $origins,
        private readonly array $methods,
        private readonly array $headers,
        private readonly int $maxAge,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (strtolower($request->getMethod()) === 'options') {
            $response = $this->responseFactory->createResponse(204);

            if ($request->getHeaderLine('access-control-request-method')) {
                $response = $response->withHeader(
                    'access-control-allow-methods',
                    implode(',', $this->methods),
                );
            }

            if ($request->getHeaderLine('access-control-request-headers')) {
                $response = $response->withHeader(
                    'access-control-allow-headers',
                    implode(',', $this->headers),
                );
            }
        } else {
            $response = $handler->handle($request);
        }

        if (in_array($request->getHeaderLine('origin'), $this->origins)) {
            $response = $response
                ->withHeader('access-control-allow-origin', $request->getHeaderLine('origin'))
                ->withHeader('access-control-max-age', (string)$this->maxAge);
        }

        return $response;
    }
}
