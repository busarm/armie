<?php

namespace Armie\Dto;

/**
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
class ResourceItemRequestDto extends BaseDto
{
    /** @var string|int Record Id */
    public string|int $id;
    /** @var array<string,string> Reqested query conditions. E.g ['name' => 'splendy1'] */
    public array $query = [];
    /** @var array<string> Reqested columns. E.g ['email', 'age'] */
    public array $columns = [];
}
