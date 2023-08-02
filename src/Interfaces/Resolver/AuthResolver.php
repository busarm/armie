<?php

namespace Busarm\PhpMini\Interfaces\Resolver;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
interface AuthResolver
{
    /**
     * Get auth data. e.g Auth token, API Key
     * 
     * @return string
     */
    public function getToken(): string;

    /**
     * Get auth user
     * 
     * @return AuthUserResolver
     */
    public function getUser(): AuthUserResolver;
}
