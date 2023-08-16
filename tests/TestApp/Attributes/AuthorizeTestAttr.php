<?php

namespace Armie\Test\TestApp\Attributes;

use Attribute;
use Armie\App;
use Armie\Exceptions\HttpException;
use Armie\Interfaces\Attribute\ClassAttributeInterface;
use Armie\Interfaces\Attribute\MethodAttributeInterface;
use Armie\Interfaces\RequestInterface;
use Armie\Interfaces\RouteInterface;
use ReflectionClass;
use ReflectionMethod;

/**
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class AuthorizeTestAttr implements ClassAttributeInterface, MethodAttributeInterface
{

    public function __construct(private string $key)
    {
    }

    /**
     * @inheritDoc
     */
    public function processClass(ReflectionClass $class, App $app, RequestInterface|RouteInterface|null $request = null): void
    {
        if ($request instanceof RequestInterface) {
            if ($request->header()->get('authorization') != $this->key) {
                throw new HttpException("Access denied", 401);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function processMethod(ReflectionMethod $method, App $app, null|RequestInterface|RouteInterface $request = null): mixed
    {
        if ($request instanceof RequestInterface) {
            if ($request->header()->get('authorization') != $this->key) {
                throw new HttpException("Access denied", 401);
            }
        }
        return null;
    }
}
