<?php

namespace Armie\Helpers;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
class Security
{
    /**
     * Generate password hash using PASSWORD_BCRYPT algo.
     *
     * @see https://www.php.net/manual/en/function.password-hash.php
     *
     * @param string|int $password
     * @param ?string    $hmacKey  Default: md5($password)
     *
     * @return string
     */
    public static function hashPassword(string|int $password, ?string $hmacKey = null)
    {
        $pwd = hash_hmac('sha256', $password, $hmacKey ?? md5($password));

        return password_hash($pwd, PASSWORD_BCRYPT);
    }

    /**
     * Verify password against password hash created with `self::hashPassword`.
     *
     * @param string|int $password
     * @param string     $passwordHash
     * @param ?string    $hmacKey      Default: md5($password)
     *
     * @return bool
     */
    public static function verifyPassword(string|int $password, string $passwordHash, ?string $hmacKey = null)
    {
        $pwd = hash_hmac('sha256', $password, $hmacKey ?? md5($password));

        return password_verify($pwd, $passwordHash);
    }

    /**
     * Clean params.
     *
     * @param array|object|string|null $input
     *
     * @return array|object|string|null
     */
    public static function clean(array|object|string|null $input): mixed
    {
        if (is_array($input) || is_object($input)) {
            return self::cleanParams((array) $input);
        } elseif (is_string($input)) {
            return self::cleanInput($input);
        } else {
            return $input;
        }
    }

    /**
     * Clean params.
     *
     * @param array $params
     *
     * @return array
     */
    public static function cleanParams(array $params): array
    {
        $list = [];
        foreach ($params as $key => $val) {
            $list[self::cleanInput($key)] = self::clean($val);
        }

        return $list;
    }

    /**
     * Clean params for query and return param keys with placeholders.
     *
     * @param array $params
     *
     * @return array
     */
    public static function cleanQueryParamKeys(array $params): array
    {
        $list = [];
        foreach (array_keys($params) as $key) {
            $list[self::cleanInput($key)] = '?';
        }

        return $list;
    }

    /**
     * Clean params for query and return param values.
     *
     * @param array $params
     *
     * @return array
     */
    public static function cleanQueryParamValues(array $params): array
    {
        $list = [];
        foreach (array_values($params) as $val) {
            $list[] = is_string($val) ? self::cleanInput($val) : (is_scalar($val) ? $val : '');
        }

        return $list;
    }

    /**
     * Strip risky elements.
     *
     * @param string $input Content to be cleaned. It MAY be modified in output
     *
     * @see https://gist.github.com/mbijon/1098477
     *
     * @author https://gist.github.com/mbijon
     */
    public static function cleanInput(string $input)
    {
        // Remove unwanted tags
        $output = self::stripTags($input);
        $output = self::stripEncodedEntities($output);

        return $output;
    }

    /**
     * Focuses on stripping entities from Base64 encoded strings.
     *
     * @param string $input Maybe Base64 encoded string
     *
     * @return string $output     Modified & re-encoded $input string
     *
     * @see https://gist.github.com/mbijon/1098477
     *
     * @author https://gist.github.com/mbijon
     */
    public static function cleanBase64(string $input)
    {
        $decoded = base64_decode($input);

        $decoded = self::stripTags($decoded);
        $decoded = self::stripEncodedEntities($decoded);

        $output = base64_encode($decoded);

        return $output;
    }

    /**
     * Focuses on stripping encoded entities
     * *** This appears to be why people use this sample code. Unclear how well Kses does this ***.
     *
     * @param string $input Content to be cleaned. It MAY be modified in output
     *
     * @return string $input  Modified $input string
     *
     * @see https://gist.github.com/mbijon/1098477
     *
     * @author https://gist.github.com/mbijon
     */
    public static function stripEncodedEntities(string $input)
    {
        // Fix &entity\n;
        $input = str_replace(['&amp;', '&lt;', '&gt;'], ['&amp;amp;', '&amp;lt;', '&amp;gt;'], $input);
        $input = preg_replace('/(&#*\w+)[\x00-\x20]+;/u', '$1;', $input);
        $input = preg_replace('/(&#x*[0-9A-F]+);*/iu', '$1;', $input);
        $input = html_entity_decode($input, ENT_COMPAT, 'UTF-8');

        // Remove any attribute starting with "on" or xmlns
        $input = preg_replace('#(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+[>\b]?#iu', '$1>', $input);

        // Remove javascript: and vbscript: protocols
        $input = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2nojavascript...', $input);
        $input = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2novbscript...', $input);
        $input = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#u', '$1=$2nomozbinding...', $input);

        // Only works in IE: <span style="width: expression(alert('Ping!'));"></span>
        $input = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?expression[\x00-\x20]*\([^>]*+>#i', '$1>', $input);
        $input = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?behaviour[\x00-\x20]*\([^>]*+>#i', '$1>', $input);
        $input = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*+>#iu', '$1>', $input);

        return $input;
    }

    /**
     * Focuses on stripping unencoded HTML tags & namespaces.
     *
     * @param string $input Content to be cleaned. It MAY be modified in output
     *
     * @return string $input  Modified $input string
     *
     * @see https://gist.github.com/mbijon/1098477
     *
     * @author https://gist.github.com/mbijon
     */
    public static function stripTags(string $input)
    {
        // Remove tags
        $input = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml)[^>]*+>#i', '', $input);

        // Remove namespaced elements
        $input = preg_replace('#</*\w+:\w[^>]*+>#i', '', $input);

        return $input;
    }
}
