<?php

namespace Busarm\PhpMini\Interfaces;

use Busarm\PhpMini\App;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
interface ProviderInterface
{
    /**
     * Provider handler
     *
     * @param App $app
     * @return void
     */
    public function process(App $app): void;
}
