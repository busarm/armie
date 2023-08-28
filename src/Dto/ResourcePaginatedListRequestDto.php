<?php

namespace Armie\Dto;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
class ResourcePaginatedListRequestDto extends ResourceListRequestDto
{
    /** @var int Requested Page. Default 1 */
    public int $page = 1;
}
