<?php

namespace Armie\Attributes\Request;

use Armie\App;
use Armie\Interfaces\Attribute\ParameterAttributeInterface;
use Armie\Interfaces\RequestInterface;
use Armie\Interfaces\Resolver\AuthUserResolver;
use Armie\Interfaces\RouteInterface;
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
class AuthUserParam implements ParameterAttributeInterface
{
    use TypeResolver;

    public function __construct()
    {
    }

    /**
     * @inheritDoc
     */
    public function processParameter(ReflectionParameter $parameter, mixed $value = null, App $app, null|RequestInterface|RouteInterface $request = null): mixed
    {
        if (
            $request instanceof RequestInterface
            && $parameter->getType() == AuthUserResolver::class
        ) {
            return $request->auth()?->getUser();
        }

        return null;
    }
}
