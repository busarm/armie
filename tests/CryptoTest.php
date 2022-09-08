<?php

namespace Busarm\PhpMini\Test;

use PHPUnit\Framework\TestCase;
use Busarm\PhpMini\Crypto;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @covers \Busarm\PhpMini\Crypto
 */
final class CryptoTest extends TestCase
{
    private $key;
    private $plainText;

    /**
     * This method is called before each test.
     */
    protected function setUp(): void
    {
        $this->key = md5(uniqid() . time());
        $this->plainText = 'Samuel Gabriel Test';
    }

    /**
     * Test encryption and decryption
     *
     * @return void
     */
    public function testEncryptAndDecrypt()
    {
        $encrypt = Crypto::encrypt($this->key, $this->plainText);
        $this->assertNotNull($encrypt);
        $this->assertNotEquals(false, base64_decode($encrypt));

        $decrypt = Crypto::decrypt($this->key, $encrypt);
        $this->assertNotNull($encrypt);
        $this->assertEquals($decrypt, $this->plainText);
    }

    
    /**
     * Test digest
     *
     * @return void
     */
    public function testDigest()
    {
        $digest = Crypto::digest($this->plainText, $this->key);
        $verifier = Crypto::digest($this->plainText, $this->key);
        $dataControl = Crypto::digest('test', $this->key);
        $keyControl = Crypto::digest($this->plainText, 'test');
        $this->assertNotNull($digest);
        $this->assertNotNull($verifier);
        $this->assertEquals($digest, $verifier);
        $this->assertNotEquals($digest, $dataControl);
        $this->assertNotEquals($digest, $keyControl);
    }
}
