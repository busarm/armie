<?php

namespace Busarm\PhpMini\Test\TestApp\Repositories;

use Busarm\PhpMini\Data\PDO\Repository;
use Busarm\PhpMini\Test\TestApp\Models\ProductTestModel;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class ProductTestRepository extends Repository
{
    public function __construct()
    {
        parent::__construct(new ProductTestModel);
    }
}
