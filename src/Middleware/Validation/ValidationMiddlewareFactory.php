<?php
declare(strict_types=1);

namespace LessHttp\Middleware\Validation;

use LessDocumentor\Route\Input\RouteInputDocumentor;
use LessValidator\Builder\TypeDocumentValidatorBuilder;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\SimpleCache\CacheInterface;

final class ValidationMiddlewareFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): ValidationMiddleware
    {
        $validatorBuilder = $container->get(TypeDocumentValidatorBuilder::class);
        assert($validatorBuilder instanceof TypeDocumentValidatorBuilder);

        $routeInputDocumentor = $container->get(RouteInputDocumentor::class);
        assert($routeInputDocumentor instanceof RouteInputDocumentor);

        $responseFactory = $container->get(ResponseFactoryInterface::class);
        assert($responseFactory instanceof ResponseFactoryInterface);

        $streamFactory = $container->get(StreamFactoryInterface::class);
        assert($streamFactory instanceof StreamFactoryInterface);

        $cache = $container->get(CacheInterface::class);
        assert($cache instanceof CacheInterface);

        $config = $container->get('config');
        assert(is_array($config));
        assert(is_array($config['routes']));
        $routes = $config['routes'];
        /** @var array<string, array<mixed>> $routes */

        return new ValidationMiddleware(
            $validatorBuilder,
            $routeInputDocumentor,
            $responseFactory,
            $streamFactory,
            $container,
            $cache,
            $routes,
        );
    }
}
