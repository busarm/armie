<?php

namespace Busarm\PhpMini\Dto;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class PaginatedCollectionDto extends BaseDto
{
    /** @var CollectionBaseDto|array */
    public CollectionBaseDto|array $data;
    /** @var int */
    public int|null $count;
    /** @var int */
    public int|null $current;
    /** @var int */
    public int|null $next;
    /** @var int */
    public int|null $previous;
    /** @var int */
    public int|null $first;
    /** @var int */
    public int|null $last;
    /** @var int */
    public int|null $total;

    public function __construct(CollectionBaseDto|array $data = null, int $page = null, int $limit = null, int $total = null, int $count = null)
    {
        if ($data !== null) {
            $this->setData($data);
        }
        if ($total !== null) {
            $this->setTotal($total);
        }
        if ($count !== null) {
            $this->setCount($count);
        }
        if ($count !== null && $total !== null && $limit !== null) {
            $this->setFirst(1);
            $this->setLast($count > 0 ? ceil($total / $limit) : 1);
            if ($page !== null) {
                $this->setCurrent(min($page, $this->last));
                $this->setNext($count > 0 ? min($page + 1, ceil($total / $limit)) : 1);
                $this->setPrevious($count > 0 ? max($page - 1, 1) : 1);
            }
        }
    }

    /**
     * Set the value of data
     *
     * @return  self
     */
    public function setData(CollectionBaseDto|array $data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Set the value of count
     *
     * @return  self
     */
    public function setCount(int $count)
    {
        $this->count = $count;

        return $this;
    }

    /**
     * Set the value of current
     *
     * @return  self
     */
    public function setCurrent(int $current)
    {
        $this->current = $current;

        return $this;
    }

    /**
     * Set the value of next
     *
     * @return  self
     */
    public function setNext(int $next)
    {
        $this->next = $next;

        return $this;
    }

    /**
     * Set the value of previous
     *
     * @return  self
     */
    public function setPrevious(int $previous)
    {
        $this->previous = $previous;

        return $this;
    }

    /**
     * Set the value of first
     *
     * @return  self
     */
    public function setFirst(int $first)
    {
        $this->first = $first;

        return $this;
    }

    /**
     * Set the value of last
     *
     * @return  self
     */
    public function setLast(int $last)
    {
        $this->last = $last;

        return $this;
    }

    /**
     * Set the value of total
     *
     * @return  self
     */
    public function setTotal(int $total)
    {
        $this->total = $total;

        return $this;
    }
}
