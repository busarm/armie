<?php

namespace Busarm\PhpMini\Dto;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class PaginatedResponseDto extends ResponseDto
{
    /** @var CollectionBaseDto|array */
    public CollectionBaseDto|array $data;
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
}
