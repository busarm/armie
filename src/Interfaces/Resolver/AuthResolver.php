<?php

namespace Armie\Interfaces\Resolver;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 *
 * @codeCoverageIgnore
 */
interface AuthResolver
{
    /**
     * Get auth data. e.g Auth token, API Key.
     *
     * @return string
     */
    public function getToken(): string;

    /**
     * Get auth user.
     *
     * @return AuthUserResolver
     */
    public function getUser(): AuthUserResolver;
}
