<?php

namespace Busarm\PhpMini\Test\TestApp\Attributes;

use Attribute;
use Busarm\PhpMini\App;
use Busarm\PhpMini\Exceptions\HttpException;
use Busarm\PhpMini\Interfaces\Attribute\ClassAttributeInterface;
use Busarm\PhpMini\Interfaces\Attribute\MethodAttributeInterface;
use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Interfaces\RouteInterface;
use ReflectionClass;
use ReflectionMethod;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
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
