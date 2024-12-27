<?php
declare(strict_types=1);

namespace LessHttp\Middleware\Authorization\Constraint;

use Psr\Http\Message\ServerRequestInterface;

final class NoOneAuthorizationConstraint implements AuthorizationConstraint
{
    public function isAllowed(ServerRequestInterface $request): bool
    {
        return false;
    }
}
