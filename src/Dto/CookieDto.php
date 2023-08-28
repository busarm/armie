<?php

namespace Armie\Dto;

use Armie\Enums\SameSite;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
class CookieDto extends BaseDto
{
    /** @var string */
    public string $name;
    /** @var string|array */
    public string|array|null $value;
    /** @var string */
    public string|null $domain;
    /** @var string */
    public string|null $path;
    /** @var string */
    public string|null $samesite;
    /** @var int */
    public int $expires = 0;
    /** @var bool */
    public bool $secure = false;
    /** @var bool */
    public bool $httponly = false;

    /**
     * @param string            $name
     * @param string|array|null $value
     * @param array             $options
     *                                   * domain: ""
     *                                   * httponly: "0"
     *                                   * expires: "0" (seconds)
     *                                   * path: "/"
     *                                   * samesite: ""
     *                                   * secure: "0"
     */
    public function __construct($name, $value = null, $options = [])
    {
        $this->name = $name;
        $this->value = $value;
        $this->domain = $options['domain'] ?? '';
        $this->path = $options['path'] ?? '';
        $this->samesite = $options['samesite'] ?? SameSite::LAX->name;
        $this->expires = intval($options['expires'] ?? 0);
        $this->secure = boolval($options['secure'] ?? false);
        $this->httponly = boolval($options['httponly'] ?? false);
    }

    /**
     * Set the value of domain.
     *
     * @return self
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * Set the value of path.
     *
     * @return self
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Set the value of samesite.
     *
     * @param string $samesite
     *
     * @return self
     */
    public function setSamesite(string $samesite)
    {
        $this->samesite = $samesite;

        return $this;
    }

    /**
     * Set the value of expires.
     *
     * @return self
     */
    public function setExpires(int $expires)
    {
        $this->expires = $expires;

        return $this;
    }

    /**
     * Set the value of secure.
     *
     * @return self
     */
    public function setSecure(bool $secure)
    {
        $this->secure = $secure;

        return $this;
    }

    /**
     * Set the value of httponly.
     *
     * @return self
     */
    public function setHttponly(bool $httponly)
    {
        $this->httponly = $httponly;

        return $this;
    }
}
