<?php

namespace Busarm\PhpMini\Traits;

use function Busarm\PhpMini\Helpers\app;

/**
 * Created by VSCODE.
 * User: Samuel
 * Date: 29/7/2022
 * Time: 11:21 AM
 */
trait Singleton
{
    /**
     * Create / Retrieve singleton instance 
     *
     * @param array $params
     * @return static
     */
    public static function make(array $params = []): static
    {
        return app()->make(static::class, $params);
    }
}
