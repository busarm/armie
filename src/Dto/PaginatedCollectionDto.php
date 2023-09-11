<?php

namespace Armie\Dto;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 *
 * @template T
 */
class PaginatedCollectionDto extends BaseDto
{
    /** @var CollectionBaseDto<T>|array<T> */
    public CollectionBaseDto|array $data = [];
    /** @var int */
    public int $count = 0;
    /** @var int */
    public int $total = 0;
    /** @var int */
    public int $current = 0;
    /** @var int */
    public int $next = 0;
    /** @var int */
    public int $previous = 0;
    /** @var int */
    public int $first = 0;
    /** @var int */
    public int $last = 0;

    public function __construct(CollectionBaseDto|array $data = null, int|null $page = null, int|null $limit = null, int|null $total = null)
    {
        if ($data !== null) {
            $this->setData($data);
            $this->setCount(count($data));
        }
        if ($total !== null) {
            $this->setTotal($total);
        }
        if ($limit !== null && $this->count > 0 && $this->total > 0) {
            $limit = $limit ?: 1;
            $this->setFirst(1);
            $this->setLast((int)ceil($this->total / $limit));
            if ($page !== null) {
                $page = $page ?: 1;
                $this->setCurrent(min($page, $this->last));
                $this->setNext(min($page + 1, ceil($this->total / $limit)));
                $this->setPrevious(max($page - 1, 1));
            }
        }
    }

    /**
     * Set the value of data.
     *
     * @return self
     */
    public function setData(CollectionBaseDto|array $data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Set the value of count.
     *
     * @return self
     */
    public function setCount(int $count)
    {
        $this->count = $count;

        return $this;
    }

    /**
     * Set the value of current.
     *
     * @return self
     */
    public function setCurrent(int $current)
    {
        $this->current = $current;

        return $this;
    }

    /**
     * Set the value of next.
     *
     * @return self
     */
    public function setNext(int $next)
    {
        $this->next = $next;

        return $this;
    }

    /**
     * Set the value of previous.
     *
     * @return self
     */
    public function setPrevious(int $previous)
    {
        $this->previous = $previous;

        return $this;
    }

    /**
     * Set the value of first.
     *
     * @return self
     */
    public function setFirst(int $first)
    {
        $this->first = $first;

        return $this;
    }

    /**
     * Set the value of last.
     *
     * @return self
     */
    public function setLast(int $last)
    {
        $this->last = $last;

        return $this;
    }

    /**
     * Set the value of total.
     *
     * @return self
     */
    public function setTotal(int $total)
    {
        $this->total = $total;

        return $this;
    }
}
