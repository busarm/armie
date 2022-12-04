<?php

namespace Busarm\PhpMini\Data\PDO;

use Busarm\PhpMini\Interfaces\SingletonInterface;
use Busarm\PhpMini\Traits\Singleton;
use PDO;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class Connection extends PDO implements SingletonInterface
{
    use Singleton;

    /**
     * Undocumented function
     *
     * @param ConnectionConfig $config
     * @throws \PDOException — if the attempt to connect to the requested database fails.
     */
    public function __construct(ConnectionConfig $config)
    {
        $dns = $config->dsn ?? sprintf("%s:dbname=%s;host=%s;port=%s", $config->driver, $config->database, $config->host, $config->port);
        parent::__construct($dns, $config->user, $config->password, array_merge([
            self::ATTR_AUTOCOMMIT => true,
            self::ATTR_PERSISTENT => $config->persist,
            self::ATTR_ERRMODE => $config->errorMode ? self::ERRMODE_EXCEPTION : self::ERRMODE_SILENT,
        ], $config->options));
    }

    /**
     * Get limit offset
     * @param int $page
     * @param int $limit
     * @return int
     */
    public function getOffset($page, $limit)
    {
        $page = intval(strip_tags(stripslashes($page)));
        $limit = intval(strip_tags(stripslashes($limit)));
        return ($page >= 1 ? $page - 1 : 0) * $limit;
    }

    /**
     * Apply limit to query
     * @param string $query
     * @param int $page
     * @param int $limit
     * @return string
     */
    public function applyLimit($query, $page, $limit)
    {
        $regexp = $this->matchLimitQuery($query);
        $page = intval(strip_tags(stripslashes($page)));
        $limit = intval(strip_tags(stripslashes($limit)));

        // Remove limit
        if ($page == 0 && $limit == 0) {
            // Check if limit is present in query
            if ($regexp) {
                return preg_replace($regexp, "", $query);
            } else {
                return $query;
            }
        }
        // Add limit
        else {
            $offset = $this->getOffset($page, $limit);
            // Check if limit is present in query
            if ($regexp) {
                return preg_replace(
                    $regexp,
                    "LIMIT $offset, $limit",
                    $query
                );
            } else {
                $query = rtrim($query, ';');
                return "$query LIMIT $offset, $limit";
            }
        }
    }

    /**
     * Apply count * to query
     * @param string $query
     * @return string|false
     */
    public function applyCount($query)
    {
        // Check if select statement is present in query
        if ($regexp = $this->matchSelectQuery($query)) {
            $query = $this->applyLimit($query, 0, 0);
            return preg_replace(
                $regexp,
                "SELECT COUNT(*) FROM",
                $query
            );
        }
        return false;
    }

    /**
     * Check if query statement is a SELECT query
     *
     * @param string $query
     * @return string|boolean Return select regexp is matched else false
     */
    public function matchSelectQuery(string $query)
    {
        $regexp = "/select\s*\n*.*\n*\s*from/im";
        return preg_match($regexp, $query) ? $regexp : false;
    }

    /**
     * Check if query statement is a INSERT query
     *
     * @param string $query
     * @return string|boolean Return select regexp is matched else false
     */
    public function matchInsertQuery(string $query)
    {
        $regexp = "/insert\s*\n*\s*into/im";
        return preg_match($regexp, $query) ? $regexp : false;
    }

    /**
     * Check if query statement is a UPDATE query
     *
     * @param string $query
     * @return string|boolean Return select regexp is matched else false
     */
    public function matchUpdateQuery(string $query)
    {
        $regexp = "/update\s*\n*.*\n*\s*set/im";
        return preg_match($regexp, $query) ? $regexp : false;
    }

    /**
     * Check if query statement is a DELETE query
     *
     * @param string $query
     * @return string|boolean Return select regexp is matched else false
     */
    public function matchDeleteQuery(string $query)
    {
        $regexp = "/delete\s*\n*\s*from/im";
        return preg_match($regexp, $query) ? $regexp : false;
    }

    /**
     * Check if query statement contains LIMIT query
     *
     * @param string $query
     * @return string|boolean Return select regexp is matched else false
     */
    public function matchLimitQuery(string $query)
    {
        $regexp = "/limit\s*([0-9]+(,\s*[0-9])*)/im";
        return preg_match($regexp, $query) ? $regexp : false;
    }
}
