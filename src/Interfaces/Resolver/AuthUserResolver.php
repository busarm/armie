<?php

namespace Busarm\PhpMini\Interfaces\Resolver;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
interface AuthUserResolver
{
    /**
     * Get auth user id
     * 
     * @return int|string
     */
    public function getUserId(): int|string;
    /**
     * Get auth user name
     * 
     * @return string
     */
    public function getUserName(): string;
    /**
     * Get auth user email
     * 
     * @return string
     */
    public function getUserEmail(): string;
}
