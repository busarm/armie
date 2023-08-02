<?php

namespace Busarm\PhpMini\Attributes\Request;

use Attribute;
use Busarm\PhpMini\App;
use Busarm\PhpMini\Exceptions\BadRequestException;
use Busarm\PhpMini\Interfaces\Attribute\ParameterAttributeInterface;
use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Interfaces\RouteInterface;
use Busarm\PhpMini\Traits\TypeResolver;
use ReflectionParameter;

/**
 * Request body parameters resolver
 * 
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
class BodyParam implements ParameterAttributeInterface
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
            $value = $request->request()->get($this->name, $value, $this->sanitize);
            if ($this->required && !isset($value)) {
                throw new BadRequestException(sprintf("%s body param is required", $this->name));
            }
            return $this->resolveType($parameter->getType() ?: $this->findType($value), $value);
        }
        return null;
    }
}
