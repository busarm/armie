<?php

namespace Busarm\PhpMini\Test\TestApp\Views;

use Busarm\PhpMini\View;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class TestComponent extends View
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    public function render()
    {
        return $this->include('test_component');
    }
}
