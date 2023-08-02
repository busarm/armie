<?php

namespace Busarm\PhpMini\Dto;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class ResourcePaginatedListRequestDto extends ResourceListRequestDto
{
    /** @var int Requested Page. Default 1 */
    public int $page = 1;
}
