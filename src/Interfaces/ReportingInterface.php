<?php

namespace Armie\Interfaces;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 *
 * @codeCoverageIgnore
 */
interface ReportingInterface
{
    /**
     * Leave breadcrumbs for issue tracking.
     *
     * @param string $title
     * @param array  $metadata
     *
     * @return void
     */
    public function leaveCrumbs(string $title, array $metadata = []);

    /**
     * Get bread crumbs.
     */
    public function getBreadCrumbs();

    /**
     * Report Info.
     *
     * @param array $data
     *
     * @return void
     */
    public function info(array $data);

    /**
     * Report Error.
     *
     * @param string $message
     *
     * @return void
     */
    public function error(string $message);

    /**
     * Report Exception.
     *
     * @param \Throwable $exception
     *
     * @return void
     */
    public function exception(\Throwable $exception);

    /**
     * Add list of params to be redacted from report.
     *
     * @param array<string> $list
     *
     * @return void
     */
    public function addRedactedParams(array $list);

    /**
     * Redact params.
     *
     * @param array         $params
     * @param array<string> $redactedParams
     *
     * @return array
     */
    public function redact(array $params, $redactedParams = []): array;
}
