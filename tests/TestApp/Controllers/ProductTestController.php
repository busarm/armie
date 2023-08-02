<?php

namespace Busarm\PhpMini\Test\TestApp\Controllers;

use Busarm\PhpMini\Data\ResourceController;
use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Interfaces\ResponseInterface;
use Busarm\PhpMini\Test\TestApp\Repositories\ProductTestRepository;
use Busarm\PhpMini\Test\TestApp\Tasks\UpdateProduct;

use function Busarm\PhpMini\Helpers\await;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class ProductTestController extends ResourceController
{
    public function __construct(
        private ProductTestRepository $repository,
        private RequestInterface $request,
        private ResponseInterface $response,
    ) {
        parent::__construct($repository, $request, $response);
    }


    public function task()
    {
        return await(new UpdateProduct(['name' => md5(uniqid())]));
    }

    public function db()
    {
        return $this->repository->findById(2);
    }

    public function dbAsync()
    {
        return await(fn () => $this->repository->findById(2));
    }
}
