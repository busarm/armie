<?php

namespace Busarm\PhpMini\Test\TestApp\Tasks;

use Busarm\PhpMini\Tasks\Task;
use Busarm\PhpMini\Test\TestApp\Repositories\ProductTestRepository;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
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

        print_r("Async product 2 update start" . PHP_EOL);
        $repository->updateById(2, $this->data);
        print_r("Async product 2 update finish" . PHP_EOL);

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
