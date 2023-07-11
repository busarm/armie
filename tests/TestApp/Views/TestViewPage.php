<?php

namespace Busarm\PhpMini\Test\TestApp\Views;

use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\View;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class TestViewPage extends View
{
    public function __construct(private RequestInterface $request)
    {
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    public function render()
    {
    ?>
        <html>

        <body>
            <?= $this->include('test_component') ?>
            <?= $this->request->version() ?>
            <h2>Test View Page</h2>
        </body>

        </html>
    <?php
    }
}
