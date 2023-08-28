<?php

namespace Armie\Attributes\Request;

use Armie\App;
use Armie\Exceptions\BadRequestException;
use Armie\Interfaces\Attribute\ParameterAttributeInterface;
use Armie\Interfaces\RequestInterface;
use Armie\Interfaces\RouteInterface;
use Armie\Traits\TypeResolver;
use Attribute;
use ReflectionParameter;

/**
 * Request query parameters resolver.
 *
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
class QueryParam implements ParameterAttributeInterface
{
    use TypeResolver;

    public function __construct(private string $name, private bool $required = false, private bool $sanitize = false)
    {
    }

    /**
     * @inheritDoc
     */
    public function processParameter(ReflectionParameter $parameter, mixed $value = null, App $app, null|RequestInterface|RouteInterface $request = null): mixed
    {
        if ($request instanceof RequestInterface) {
            $value = $request->query()->get($this->name, $value, $this->sanitize);
            if ($this->required && !isset($value)) {
                throw new BadRequestException(sprintf('%s query param is required', $this->name));
            }

            return $this->resolveType($parameter->getType() ?: $this->findType($value), $value);
        }

        return null;
    }
}
