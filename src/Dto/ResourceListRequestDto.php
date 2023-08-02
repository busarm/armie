<?php

namespace Busarm\PhpMini\Dto;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class ResourceListRequestDto extends BaseDto
{
    /** @var int Requested list limit. Default 0 */
    public int $limit = 0;
    /** @var array<string,string> Reqested query conditions. E.g ['name' => 'splendy1'] */
    public array $query = [];
    /** @var array<string> Reqested columns. E.g ['email', 'age'] */
    public array $columns = [];
}
