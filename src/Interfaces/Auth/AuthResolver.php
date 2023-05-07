<?php

namespace Busarm\PhpMini\Interfaces\Auth;

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
     * @return mixed
     */
    public function getData(): mixed;

    /**
     * Get auth user
     * 
     * @return AuthUserResolver
     */
    public function getUser(): AuthUserResolver;
}
