<?php

namespace Busarm\PhpMini;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class Crypto
{
    /**
     * For a list of available cipher methods. Default: AES-256-CBC. @see `\openssl_get_cipher_methods` for list of supported methods
     * @var string
     */
    public static $METHOD = "AES-256-CBC";
    /**
     * Name of selected hashing algorithm (i.e. "md5", "sha256", "haval160,4", etc..). Default: md5. @see `\hash_algos` for a list of supported algorithms
     * @var string
     */
    public static $KEY_HASH_ALGO = "md5";
    /**
     * Name of selected hashing algorithm (i.e. "md5", "sha256", "haval160,4", etc..). Default: sha1. @see `\hash_algos` for a list of supported algorithms
     * @var string
     */
    public static $HMAC_HASH_ALGO = "sha1";
    /**
     * Number of key hash iterations. @see `\hash_pbkdf2`. Default: 8.
     * @var string
     */
    public static $KEY_HASH_ITERATIONS = 8;
    /**
     * Length of key hash. @see `\hash_pbkdf2`. Default: 16.
     * @var string
     */
    public static $KEY_HASH_LENGTH = 16;
    /**
     * Length of key salt. @see `\openssl_random_pseudo_bytes`. Default: 8.
     * @var string
     */
    public static $KEY_SALT_LENGTH = 8;
    /**
     * Length of key iv. @see `\openssl_random_pseudo_bytes`. Default: 16.
     * @var string
     */
    public static $KEY_IV_LENGTH = 16;

    /**
     * Encrypt Data for client
     * 
     * @param string $paraphrase  Encryption Key
     * @param string $plain Data to encrypt
     * @param array $configs Encryption configs
     * - `METHOD` - Default: AES-256-CBC. @see `\openssl_get_cipher_methods`
     * - `KEY_HASH_ALGO` - Default: md5. @see `\hash_algos`
     * - `HMAC_HASH_ALGO` - Default: sha1. @see `\hash_algos`
     * - `KEY_HASH_ITERATIONS` - Default: 8. @see `\hash_pbkdf2`
     * - `KEY_HASH_LENGTH` - Default: 16. @see `\hash_pbkdf2`
     * - `KEY_SALT_LENGTH` - Default: 8. @see `\openssl_random_pseudo_bytes`
     * - `KEY_IV_LENGTH` - Default: 16. @see `\openssl_random_pseudo_bytes`
     * @return mixed Base64 encoded result of encrypted data
     */
    public static function encrypt(string|null $passphrase, string|null $plain, array $configs = [])
    {
        $method = $configs['METHOD'] ?? self::$METHOD;
        $keyHashAlgo = $configs['KEY_HASH_ALGO'] ?? self::$KEY_HASH_ALGO;
        $hmacHashAlgo = $configs['HMAC_HASH_ALGO'] ?? self::$HMAC_HASH_ALGO;
        $keyHashIterations = $configs['KEY_HASH_ITERATIONS'] ?? self::$KEY_HASH_ITERATIONS;
        $keyHashLength = $configs['KEY_HASH_LENGTH'] ?? self::$KEY_HASH_LENGTH;
        $keySaltLength = $configs['KEY_SALT_LENGTH'] ?? self::$KEY_SALT_LENGTH;
        $keyIvLength = $configs['KEY_IV_LENGTH'] ?? self::$KEY_IV_LENGTH;

        if (!empty($passphrase)) {
            $salt = openssl_random_pseudo_bytes($keySaltLength);
            $iv = openssl_random_pseudo_bytes($keyIvLength);
            $key = hash_pbkdf2($keyHashAlgo, $passphrase, $salt, $keyHashIterations, $keyHashLength, true);
            $crypt = base64_encode(openssl_encrypt($plain, $method, $key, OPENSSL_RAW_DATA, $iv));
            $hash = self::digest($crypt, md5($passphrase), $hmacHashAlgo);
            $data =  $crypt . '*' . bin2hex($salt) . '*' . bin2hex($iv) . '*' . $hash;
            return base64_encode($data);
        }
        return false;
    }


    /**
     * Decrypt Data from client
     * 
     * @param string $paraphrase  Encryption Key
     * @param string $cipher Data to decrypt
     * @param array $configs Encryption configs
     * - `METHOD` - Default: AES-256-CBC. @see `\openssl_get_cipher_methods`
     * - `KEY_HASH_ALGO` - Default: md5. @see `\hash_algos`
     * - `HMAC_HASH_ALGO` - Default: sha1. @see `\hash_algos`
     * - `KEY_HASH_ITERATIONS` - Default: 8. @see `\hash_pbkdf2`
     * - `KEY_HASH_LENGTH` - Default: 16. @see `\hash_pbkdf2`
     * @return string|boolean
     */
    public static function decrypt(string|null $passphrase, string|null $cipher, array $configs = [])
    {

        $method = $configs['METHOD'] ?? self::$METHOD;
        $keyHashAlgo = $configs['KEY_HASH_ALGO'] ?? self::$KEY_HASH_ALGO;
        $hmacHashAlgo = $configs['HMAC_HASH_ALGO'] ?? self::$HMAC_HASH_ALGO;
        $keyHashIterations = $configs['KEY_HASH_ITERATIONS'] ?? self::$KEY_HASH_ITERATIONS;
        $keyHashLength = $configs['KEY_HASH_LENGTH'] ?? self::$KEY_HASH_LENGTH;

        if (!empty($passphrase) && !empty($cipher) && ($data = explode('*', base64_decode($cipher)))) {
            $crypt = $data[0] ?? null;
            $salt = $data[1] ?? null;
            $iv  = $data[2] ?? null;
            $hash  = $data[3] ?? null;
            if ($crypt && $salt && $iv && $hash) {
                $salt = hex2bin($salt);
                $iv = hex2bin($iv);
                $key = hash_pbkdf2($keyHashAlgo, $passphrase, $salt, $keyHashIterations, $keyHashLength, true);
                if ($hash == self::digest($crypt, md5($passphrase), $hmacHashAlgo)) {
                    return openssl_decrypt(base64_decode($crypt), $method, $key, OPENSSL_RAW_DATA, $iv);
                }
            }
        }
        return false;
    }


    /**
     * Generate hmac signature for data
     * 
     * @param string $data String Data 
     * @param string $key hmac key
     * @param string $algo hmac algo. @see `\hash_algos`
     * @return string|boolean
     */
    public static function digest(string $data, string $key, string |null $algo = NULL)
    {
        $algo = $algo ?? self::$HMAC_HASH_ALGO;
        return hash_hmac($algo, $data, $key);
    }
}
