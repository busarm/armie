<?php

namespace Busarm\PhpMini\Interfaces;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
interface ErrorReportingInterface
{
    /**
     * Set up error reporting
     *
     * @return void
     */
    public function setupReporting();

    /**
     * Leave breadcrumbs for issue tracking
     *
     * @param mixed $title
     * @param array $metadata
     * @return void
     */
    public function leaveCrumbs($title, array $metadata = []);

    /**
     * Report Error
     *
     * @param string $heading
     * @param string $message
     * @param string $file
     * @param int $line
     * @return void
     */
    public function reportError($heading, $message, $file = null, $line = null);

    /**
     * Report Exception
     *
     * @param \Throwable $exception
     * @return void
     */
    public function reportException($exception);
}