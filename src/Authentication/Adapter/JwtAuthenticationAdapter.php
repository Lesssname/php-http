<?php
declare(strict_types=1);

namespace LessHttp\Authentication\Adapter;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use LessValueObject\Composite\Reference;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final class JwtAuthenticationAdapter implements AuthenticationAdapter
{
    private const AUTHORIZATION_REGEXP = <<<'REGEXP'
/^Bearer ([a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+)$/
REGEXP;

    /**
     * @param array<mixed> $keys
     */
    public function __construct(private array $keys)
    {}

    public function resolve(ServerRequestInterface $request): ?Reference
    {
        $header = $request->getHeaderLine('authorization');

        if (preg_match(self::AUTHORIZATION_REGEXP, $header, $matches) === 1) {
            try {
                $claims = $this->getClaims($matches[1]);
            } catch (Throwable) {
                return null;
            }

            if (isset($claims->sub)) {
                assert(is_string($claims->sub));

                return Reference::fromString($claims->sub);
            }
        }

        return null;
    }

    private function getClaims(string $token): object
    {
        return JWT::decode(
            $token,
            array_map(
                function (array $settings): Key {
                    $keyMaterial = file_get_contents($settings['keyMaterial']);
                    assert(is_string($keyMaterial));

                    return new Key(
                        trim($keyMaterial),
                        $settings['algorithm'],
                    );
                },
                $this->keys,
            ),
        );
    }
}
