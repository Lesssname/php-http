<?php
declare(strict_types=1);

namespace LessHttp\Authentication\Adapter;

use LessValueObject\Composite\ForeignReference;
use Psr\Http\Message\ServerRequestInterface;

interface AuthenticationAdapter
{
    public function resolve(ServerRequestInterface $request): ?ForeignReference;
}
