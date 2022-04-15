<?php

namespace App\Service;

use RuntimeException;

class CryptService
{
    private const CIPHER = "aes-128-cbc";
    private const PAD = '*';
    private string $secret;

    public function __construct(string $secret)
    {
        $this->secret = $secret;
    }

    public function encrypt(string $input, string $base): string
    {
        $length = openssl_cipher_iv_length(self::CIPHER);
        $cipherText = openssl_encrypt(
            $input,
            self::CIPHER,
            $this->secret,
            OPENSSL_RAW_DATA,
            substr(str_pad($base, $length, self::PAD), 0, $length)
        );
        if (false === $cipherText) {
            throw new RuntimeException(openssl_error_string());
        }
        return base64_encode($cipherText);
    }

    public function decrypt(string $input, string $base): string
    {
        $length = openssl_cipher_iv_length(self::CIPHER);
        $text = openssl_decrypt(
            base64_decode($input),
            self::CIPHER,
            $this->secret,
            OPENSSL_RAW_DATA,
            substr(str_pad($base, $length, self::PAD), 0, $length)
        );
        if (false === $text) {
            throw new RuntimeException(openssl_error_string()());
        }
        return $text;
    }
}
