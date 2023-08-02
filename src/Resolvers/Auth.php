<?php

namespace Busarm\PhpMini\Resolvers;

use Busarm\PhpMini\Interfaces\Resolver\AuthResolver;
use Busarm\PhpMini\Interfaces\Resolver\AuthUserResolver;

/**
 * 
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class Auth implements AuthResolver
{

    public function __construct(private string $token, private AuthUserResolver $user)
    {
    }


    /**
     * Get the value of token
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * Set the value of token
     *
     * @return  self
     */
    public function setToken($token): self
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Get the value of user
     */
    public function getUser(): AuthUserResolver
    {
        return $this->user;
    }

    /**
     * Set the value of user
     *
     * @return  self
     */
    public function setUser($user): self
    {
        $this->user = $user;

        return $this;
    }
}
