<?php

namespace Armie\Helpers;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
class Parser
{
    /**
     * Parse params for query and return param keys with placeholders (?).
     *
     * @param array $params
     * @param bool  $sanitize
     *
     * @return array
     */
    public static function parseQueryParamKeys(array $params, $sanitize = true): array
    {
        $list = [];
        foreach (array_keys($params) as $key) {
            $list[$sanitize ? Security::cleanInput($key) : $key] = '?';
        }

        return $list;
    }

    /**
     * Parse params for query and return param values only.
     *
     * @param array $params
     * @param bool  $sanitize
     *
     * @return array
     */
    public static function parseQueryParamValues(array $params, $sanitize = true): array
    {
        $list = [];
        foreach (array_values($params) as $val) {
            $list[] = is_string($val) ? ($sanitize ? Security::cleanInput($val) : $val) : (is_scalar($val) ? $val : '');
        }

        return $list;
    }
}
