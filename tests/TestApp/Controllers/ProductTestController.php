<?php

namespace Armie\Test\TestApp\Controllers;

use Armie\Data\ResourceController;
use Armie\Interfaces\RequestInterface;
use Armie\Interfaces\ResponseInterface;
use Armie\Test\TestApp\Repositories\ProductTestRepository;
use Armie\Test\TestApp\Tasks\UpdateProduct;

use function Armie\Helpers\async;

/**
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
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
        async(new UpdateProduct(['name' => md5(uniqid())]));
    }

    public function db()
    {
        return $this->repository->findById(2);
    }

    public function dbAsync()
    {
        $repository = $this->repository;
        async(fn () => $repository->findById(2));
    }
}
