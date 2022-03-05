<?php
declare(strict_types=1);

namespace LessHttp\Middleware\Authorization;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class AuthorizationMiddlewareFactory
{
    public const ROUTE_KEY = 'authorizations';

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): AuthorizationMiddleware
    {
        $responseFactory = $container->get(ResponseFactoryInterface::class);
        assert($responseFactory instanceof ResponseFactoryInterface);

        $streamFactory = $container->get(StreamFactoryInterface::class);
        assert($streamFactory instanceof StreamFactoryInterface);

        $config = $container->get('config');
        assert(is_array($config));

        assert(is_array($config['routes']));

        return new AuthorizationMiddleware(
            $responseFactory,
            $streamFactory,
            $container,
            $this->parseAuthorizations($config['routes']),
        );
    }

    /**
     * @param array<mixed> $routes
     *
     * @return array<string, array<string>>
     *
     * @psalm-suppress MixedInferredReturnType
     * @psalm-suppress MixedReturnTypeCoercion
     * @psalm-suppress MixedReturnStatement
     */
    private function parseAuthorizations(array $routes): array
    {
        return array_map(
            static fn(array $route): array => $route[self::ROUTE_KEY],
            array_filter(
                $routes,
                static fn(array $route): bool => isset($route[self::ROUTE_KEY])
                    && $route[self::ROUTE_KEY],
            ),
        );
    }
}
