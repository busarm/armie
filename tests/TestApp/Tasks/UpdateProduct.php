<?php

namespace Armie\Test\TestApp\Tasks;

use Armie\Tasks\Task;
use Armie\Test\TestApp\Repositories\ProductTestRepository;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
class UpdateProduct extends Task
{
    public function __construct(protected array $data = [])
    {
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    public function run(): mixed
    {
        $repository = new ProductTestRepository();

        print_r('Async product 2 update start'.PHP_EOL);
        $repository->updateById(2, $this->data);
        print_r('Async product 2 update finish'.PHP_EOL);

        return $repository->findById(2);
    }

    /**
     * @inheritdoc
     */
    public function getParams(): array
    {
        return [
            'data' => $this->data,
        ];
    }
}
