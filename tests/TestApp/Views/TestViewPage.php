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
    public function __construct(private RequestInterface $request, private string $name)
    {
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    public function render()
    {
        $header = new TestComponent;
        return <<<HTML
        <html>
            <body>
                <div>{$header}</div>
                <div>{$this->name}</div>
                <div>{$this->request->version()}</div>
                <div>{$this->request->header()->get('host')}</div>
                <h2>Test View Page</h2>
            </body>

        </html>
        HTML;
    }
}
