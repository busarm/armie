<?php

namespace Armie\Enums;

/**
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
enum Env: string
{
    case LOCAL     =   "local";
    case DEV       =   "development";
    case TEST      =   "testing";
    case STG       =   "staging";
    case UAT       =   "uat";
    case PROD      =   "production";

    /**
     * Parse env string
     *
     * @param ?string $env
     * @return self
     */
    public static function parse(?string $env): self
    {
        return match (strtolower($env ?? '')) {
            'prod', Env::PROD->value => Env::PROD,
            Env::UAT->value => Env::UAT,
            'stg', Env::STG->value => Env::STG,
            'test', Env::TEST->value => Env::TEST,
            'dev', 'develop', Env::DEV->value => Env::DEV,
            default => Env::LOCAL
        };
    }
}
