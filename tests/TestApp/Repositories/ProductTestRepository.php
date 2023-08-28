<?php

namespace Armie\Test\TestApp\Repositories;

use Armie\Data\PDO\Repository;
use Armie\Test\TestApp\Models\ProductTestModel;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
class ProductTestRepository extends Repository
{
    public function __construct()
    {
        parent::__construct(new ProductTestModel());
    }
}
