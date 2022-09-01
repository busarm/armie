<?php

use Busarm\PhpMini\App;
use Busarm\PhpMini\Config;
use PHPUnit\Framework\TestCase;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
final class AppTest extends TestCase {
    
    public static function setupBeforeClass(): void
    {
        ini_set('error_log', tempnam(sys_get_temp_dir(), 'php-mini'));
    }

    public function testInitializeApp()
    {
        define('APP_START_TIME', floor(microtime(true) * 1000));
        $config = (new Config())
        ->setBasePath(dirname(__FILE__))
        ->setAppPath('TestApp')
        ->setConfigPath('Configs')
        ->setViewPath('Views');
        $app = new App($config);
        $this->assertNotEmpty($app);
    }
}