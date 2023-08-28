<?php

namespace Armie\Attributes\Request;

use Armie\App;
use Armie\Interfaces\Attribute\ParameterAttributeInterface;
use Armie\Interfaces\RequestInterface;
use Armie\Interfaces\Resolver\AuthResolver;
use Armie\Interfaces\RouteInterface;
use Armie\Resolvers\Auth;
use Armie\Traits\TypeResolver;
use Attribute;
use ReflectionParameter;

/**
 * Request auth user resolver.
 *
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
class AuthParam implements ParameterAttributeInterface
{
    use TypeResolver;

    public function __construct(private string $name, private bool $sanitize = false)
    {
    }

    /**
     * @inheritDoc
     */
    public function processParameter(ReflectionParameter $parameter, mixed $value = null, App $app, null|RequestInterface|RouteInterface $request = null): mixed
    {
        if (
            $request instanceof RequestInterface && $parameter
            && ($parameter->getType() == AuthResolver::class || $parameter->getType() == Auth::class)
        ) {
            return $request->auth();
        }

        return null;
    }
}
