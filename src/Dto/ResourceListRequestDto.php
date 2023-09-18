<?php

namespace Armie\Dto;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
class ResourceListRequestDto extends BaseDto
{
    /** @var int Requested list limit. Default 0 */
    public int $limit = 0;
    /** @var array<string,string> Reqested query conditions. E.g ['name' => 'splendy1'] */
    public array $query = [];
    /** @var array<string> Reqested columns. E.g ['email', 'age'] */
    public array $columns = [];
    /** @var array<string, string> Reqested columns. E.g ['name' => 'DESC'] */
    public array $sort = [];
}
