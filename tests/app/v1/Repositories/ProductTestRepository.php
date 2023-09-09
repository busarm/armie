<?php

namespace Armie\Tests\App\V1\Repositories;

use Armie\Data\PDO\Repository;
use Armie\Tests\App\V1\Models\ProductTestModel;

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
