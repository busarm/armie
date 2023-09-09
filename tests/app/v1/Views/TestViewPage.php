<?php

namespace Armie\Tests\App\V1\Views;

use Armie\Interfaces\RequestInterface;
use Armie\View;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
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
        $header = new TestComponent();

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
