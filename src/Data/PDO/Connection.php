<?php

namespace Armie\Data\PDO;

use Armie\Configs\PDOConfig;
use Armie\Interfaces\SingletonInterface;
use Armie\Traits\Singleton;
use PDO;

use function Armie\Helpers\app;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
class Connection extends PDO implements SingletonInterface
{
    use Singleton;

    /**
     * @param PDOConfig $config
     * @param int       $id
     *
     * @throws \PDOException — if the attempt to connect to the requested database fails.
     *
     * @inheritDoc
     */
    public function __construct(PDOConfig $config, public $id = 0)
    {
        $dns = $config->connectionDNS ?? sprintf(
            '%s:dbname=%s;host=%s;port=%s',
            $config->connectionDriver,
            $config->connectionDatabase,
            $config->connectionHost,
            $config->connectionPort
        );
        parent::__construct($dns, $config->connectionUsername, $config->connectionPassword, array_merge([
            self::ATTR_AUTOCOMMIT => true,
            self::ATTR_PERSISTENT => $config->connectionPersist,
            self::ATTR_ERRMODE    => $config->connectionErrorMode ? self::ERRMODE_EXCEPTION : self::ERRMODE_SILENT,
        ], $config->connectionOptions));
    }

    /**
     * Create / Retrieve singleton instance.
     *
     * @param array $params
     *
     * @return static
     */
    public static function make(array $params = []): static
    {
        // Async mode - use pooling
        if (app()->async && app()->config->db->connectionPoolSize > 0) {
            /** @var static */
            return ConnectionPool::make([
                'config' => app()->config->db
            ])->get();
        }

        return app()->make(static::class, $params);
    }

    /**
     * Get limit offset.
     *
     * @param int $page
     * @param int $limit
     *
     * @return int
     */
    public function getOffset(int $page, int $limit)
    {
        return ($page >= 1 ? $page - 1 : 0) * $limit;
    }

    /**
     * Apply limit to query.
     *
     * @param string $query
     * @param int    $page
     * @param int    $limit
     *
     * @return string
     */
    public function applyLimit(string $query, int $page, int $limit)
    {
        $regexp = $this->matchLimitQuery($query);

        // Remove limit
        if ($page == 0 && $limit == 0) {
            // Check if limit is present in query
            if ($regexp) {
                return preg_replace($regexp, '', $query);
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
     * Apply count * to query.
     *
     * @param string $query
     *
     * @return string|false
     */
    public function applyCount(string $query)
    {
        // Check if select statement is present in query
        if ($regexp = $this->matchSelectQuery($query)) {
            $query = $this->applyLimit($query, 0, 0);

            return preg_replace(
                $regexp,
                'SELECT COUNT(*) FROM',
                $query
            );
        }

        return false;
    }

    /**
     * Check if query statement is a SELECT query.
     *
     * @param string $query
     *
     * @return string|bool Return select regexp is matched else false
     */
    public function matchSelectQuery(string $query)
    {
        $regexp = "/select\s*\n*.*\n*\s*from/im";

        return preg_match($regexp, $query) ? $regexp : false;
    }

    /**
     * Check if query statement is a INSERT query.
     *
     * @param string $query
     *
     * @return string|bool Return select regexp is matched else false
     */
    public function matchInsertQuery(string $query)
    {
        $regexp = "/insert\s*\n*\s*into/im";

        return preg_match($regexp, $query) ? $regexp : false;
    }

    /**
     * Check if query statement is a UPDATE query.
     *
     * @param string $query
     *
     * @return string|bool Return select regexp is matched else false
     */
    public function matchUpdateQuery(string $query)
    {
        $regexp = "/update\s*\n*.*\n*\s*set/im";

        return preg_match($regexp, $query) ? $regexp : false;
    }

    /**
     * Check if query statement is a DELETE query.
     *
     * @param string $query
     *
     * @return string|bool Return select regexp is matched else false
     */
    public function matchDeleteQuery(string $query)
    {
        $regexp = "/delete\s*\n*\s*from/im";

        return preg_match($regexp, $query) ? $regexp : false;
    }

    /**
     * Check if query statement contains LIMIT query.
     *
     * @param string $query
     *
     * @return string|bool Return select regexp is matched else false
     */
    public function matchLimitQuery(string $query)
    {
        $regexp = "/limit\s*([0-9]+(,\s*[0-9])*)/im";

        return preg_match($regexp, $query) ? $regexp : false;
    }

    /**
     * Execute query.
     *
     * @param string $query  Model Provider Query. e.g SQL query
     * @param array  $params Query Params. e.g SQL query params `[$id]` or [':id' => $id]
     *
     * @return int|bool Returns row count for modification query or boolean success status
     */
    public function executeQuery(string $query, array $params = []): int|bool
    {
        if (!empty($query)) {
            $stmt = $this->prepare($query);
            if ($stmt && $stmt->execute($params)) {
                $isEdit = $this->matchInsertQuery($query) ||
                    $this->matchUpdateQuery($query) ||
                    $this->matchDeleteQuery($query);

                return $isEdit ? $stmt->rowCount() : true;
            }
        }

        return false;
    }
}
