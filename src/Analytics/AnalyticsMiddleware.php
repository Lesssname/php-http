<?php
declare(strict_types=1);

namespace LessHttp\Analytics;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

final class AnalyticsMiddleware implements MiddlewareInterface
{
    public function __construct(
        private Connection $connection,
        private string $service,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (in_array(strtolower($request->getMethod()), ['options', 'head'], true)) {
            return $handler->handle($request);
        }

        try {
            $response = $handler->handle($request);
        } catch (Throwable $e) {
            $this->log($e, $request);

            throw $e;
        }

        $this->log($response, $request);

        return $response;
    }

    /**
     * @throws Exception
     * @throws JsonException
     */
    private function log(ResponseInterface | Throwable $result, ServerRequestInterface $request): void
    {
        $startTime = $this->getStartTimeFromRequest($request);

        if ($result instanceof Throwable) {
            $error = json_encode(['throwable' => $result->getMessage()], JSON_THROW_ON_ERROR);
            $response = 500;
        } else {
            $response = $result->getStatusCode();

            if ($response >= 400) {
                $error = strtolower($result->getHeaderLine('content-type')) !== 'application/json'
                    ? json_encode((string)$result->getBody(), JSON_THROW_ON_ERROR)
                    : (string)$result->getBody();
            } else {
                $error = null;
            }
        }

        $values = [
            'service' => $this->service,
            'method' => strtolower($request->getMethod()),
            'action' => $this->getAction($request),

            'identity' => $this->getIdentityFromRequest($request),
            'identity_role' => $this->getIdentityRoleFromRequest($request),

            'ip' => $this->getIpFromRequest($request),
            'user_agent' => $this->getUserAgentFromRequest($request),

            'requested_on' => (int)floor($startTime * 1000),

            'duration' => (int)ceil((microtime(true) - $startTime) * 1_000),

            'response' => $response,
            'error' => $error,
        ];

        $builder = $this->connection->createQueryBuilder();
        foreach ($values as $column => $value) {
            $builder->setValue("`{$column}`", ":{$column}");
            $builder->setParameter(":{$column}", $value);
        }

        $builder
            ->insert('request')
            ->executeStatement();
    }

    private function getAction(ServerRequestInterface $request): string
    {
        $path = $request->getUri()->getPath();
        $position = strrpos($path, '/');

        return is_int($position)
            ? substr($path, $position + 1)
            : $path;
    }

    private function getIpFromRequest(ServerRequestInterface $request): ?string
    {
        $ip = $request->getServerParams()['REMOTE_ADDR'] ?? null;
        assert(is_string($ip) || is_null($ip));

        return $ip;
    }

    private function getUserAgentFromRequest(ServerRequestInterface $request): ?string
    {
        $userAgent = $request->getHeaderLine('user-agent');

        return mb_substr(trim($userAgent), 0, 255) ?: null;
    }

    private function getIdentityFromRequest(ServerRequestInterface $request): ?string
    {
        $identity = $request->getAttribute('identity');
        assert($identity === null || is_string($identity));

        return $identity;
    }

    private function getIdentityRoleFromRequest(ServerRequestInterface $request): ?string
    {
        $claims = $request->getAttribute('claims');

        if (is_array($claims) && isset($claims['rol']) && is_string($claims['rol'])) {
            return $claims['rol'];
        }

        return null;
    }

    private function getStartTimeFromRequest(ServerRequestInterface $request): float
    {
        $startTime = $request->getServerParams()['REQUEST_TIME_FLOAT'];
        assert(is_float($startTime));

        return $startTime;
    }
}
