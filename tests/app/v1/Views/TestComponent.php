<?php

namespace Armie\Tests\App\V1\Views;

use Armie\View;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
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
