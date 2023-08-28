<?php

namespace Armie\Interfaces;

use Armie\App;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 *
 * @codeCoverageIgnore
 */
interface ProviderInterface
{
    /**
     * Provider handler.
     *
     * @param App $app
     *
     * @return void
     */
    public function process(App $app): void;
}
