<?php

class CryptoUrl {
    
    const METHOD        = 'aes-256-cbc';
    const SLASH_REPLACE = '_';
    
    public static function encrypt($message, $key) {
        $key = hex2bin(hash('sha256', $key));

        if (mb_strlen($key, '8bit') !== 32) {
            throw new Exception("Needs a 256-bit key!");
        }

        $ivsize = openssl_cipher_iv_length(self::METHOD);
        $iv = openssl_random_pseudo_bytes($ivsize);
        
        $ciphertext = openssl_encrypt(
            $message,
            self::METHOD,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
        
        $finalcipher = self::base64url_encode($iv . $ciphertext);

        return $finalcipher;
    }

    public static function decrypt($message, $key) {
        $key = hex2bin(hash('sha256', $key));

        if (mb_strlen($key, '8bit') !== 32) {
            throw new Exception("Needs a 256-bit key!");
        }

        $message = self::base64url_decode($message);
        $ivsize = openssl_cipher_iv_length(self::METHOD);
        $iv = mb_substr($message, 0, $ivsize, '8bit');
        $ciphertext = mb_substr($message, $ivsize, null, '8bit');
        
        return openssl_decrypt(
            $ciphertext,
            self::METHOD,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
    }

    public static function hmac_sign($message, $key) {
        $msgMAC     = hash_hmac('sha256', $message, $key);
        $message    = $message;
 
        $mac = self::base64url_encode($msgMAC . $message);

        return $mac;
    }

    public static function hmac_verify($bundle, $key) {
        $bundle     = self::base64url_decode($bundle);
        $msgMAC     = mb_substr($bundle, 0, 64, '8bit');
        $message    = mb_substr($bundle, 64, null, '8bit');

        return hash_equals(
            hash_hmac('sha256', $message, $key),
            $msgMAC
        );
    }

    public static function hmac_get_message($bundle) {
        $bundle     = self::base64url_decode($bundle);
        $message    = mb_substr($bundle, 64, null, '8bit');

        return $message;
    }

    public static function base64url_encode($s) {

        return str_replace('/', self::SLASH_REPLACE, base64_encode($s));
    }

    public static function base64url_decode($s) {

        return base64_decode(str_replace(self::SLASH_REPLACE, '/', $s));
    }
}