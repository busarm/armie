<?php

namespace Busarm\PhpMini\Interfaces;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
interface ReportingInterface
{
    /**
     * Leave breadcrumbs for issue tracking
     *
     * @param string $title
     * @param array $metadata
     * @return void
     */
    public function leaveCrumbs(string $title, array $metadata = []);

    /**
     * Report Info
     *
     * @param array $data
     * @return void
     */
    public function reportInfo(array $data);

    /**
     * Report Error
     *
     * @param string $heading
     * @param string $message
     * @param string $file
     * @param int $line
     * @return void
     */
    public function reportError(string $heading, string $message, string|null $file = null, int|null $line = null);

    /**
     * Report Exception
     *
     * @param \Throwable $exception
     * @return void
     */
    public function reportException(\Throwable $exception);


    /**
     * Add list of params to be redacted from report
     *
     * @param array<string> $list
     * @return void
     */
    public function addRedactedParams(array $list);


    /**
     * Redact params
     *
     * @param array $params
     * @param array<string> $redactedParams
     * @return array
     */
    public function redact(array $params, $redactedParams = []): array;
}
