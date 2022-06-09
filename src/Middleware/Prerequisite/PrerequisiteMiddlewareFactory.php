<?php
declare(strict_types=1);

namespace LessHttp\Middleware\Prerequisite;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class PrerequisiteMiddlewareFactory
{
    public const ROUTE_KEY = 'prerequisites';

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): PrerequisiteMiddleware
    {
        $responseFactory = $container->get(ResponseFactoryInterface::class);
        assert($responseFactory instanceof ResponseFactoryInterface);

        $streamFactory = $container->get(StreamFactoryInterface::class);
        assert($streamFactory instanceof StreamFactoryInterface);

        $config = $container->get('config');
        assert(is_array($config));

        assert(is_array($config['routes']));

        return new PrerequisiteMiddleware(
            $responseFactory,
            $streamFactory,
            $container,
            $this->parsePrerequisites($config['routes']),
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
     * @psalm-suppress MixedAssignment
     */
    private function parsePrerequisites(array $routes): array
    {
        $prerequisites = [];

        foreach ($routes as $key => $route) {
            assert(is_array($route));

            if (isset($route[self::ROUTE_KEY])) {
                $prerequisites[$key] = $route[self::ROUTE_KEY];
            }
        }

        return $prerequisites;
    }
}
