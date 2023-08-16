<?php

namespace Armie\Resolvers;

use Armie\Interfaces\Resolver\AuthResolver;
use Armie\Interfaces\Resolver\AuthUserResolver;

/**
 * 
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
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
