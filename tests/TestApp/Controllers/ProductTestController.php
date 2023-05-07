<?php

namespace Busarm\PhpMini\Test\TestApp\Controllers;

use Busarm\PhpMini\HTTP\CrudController;
use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Interfaces\ResponseInterface;
use Busarm\PhpMini\Test\TestApp\Repositories\ProductTestRepository;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class ProductTestController extends CrudController
{
    public function __construct(
        private ProductTestRepository $productTestRepository,
        private RequestInterface $request,
        private ResponseInterface $response,
    ) {
        parent::__construct($productTestRepository, $request, $response);
    }
}
