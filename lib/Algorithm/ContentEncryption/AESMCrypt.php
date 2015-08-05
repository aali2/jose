<?php

namespace SpomkyLabs\Jose\Algorithm\ContentEncryption;

/**
 */
class AESMCrypt implements AESInterface
{
    public static function encrypt($data, $k, $iv)
    {
        $resource = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
        $padded_text = self::pad($data, 16);
        mcrypt_generic_init($resource, $k, $iv);
        $cipherText = mcrypt_generic($resource, $padded_text);
        mcrypt_generic_deinit($resource);
        return $cipherText;
    }

    public static function decrypt($data, $k, $iv)
    {
        $resource = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
        mcrypt_generic_init($resource, $k, $iv);
        $decrypted = mdecrypt_generic($resource, $data);
        mcrypt_generic_deinit($resource);
        $decrypted_text = self::unpad($decrypted);
        return $decrypted_text;
    }

    public function pad($data, $block_size)
    {
        $padding = $block_size - (self::getLengthSafe($data) % $block_size);
        $pattern = chr($padding);
        return $data . str_repeat($pattern, $padding);
    }

    public function unpad($data)
    {
        $padChar = substr($data, -1);
        $padLength = ord($padChar);
        return substr($data, 0, -$padLength);
    }

    private static function getLengthSafe($str) {
        if (function_exists('mb_strlen')) {
            $length = mb_strlen($str, '8bit');
            if ($length === false) {
                throw new \Exception("Invalid encoding for mb_strlen()");
            }
            return $length;
        } else {
            return strlen($str);
        }
    }
}