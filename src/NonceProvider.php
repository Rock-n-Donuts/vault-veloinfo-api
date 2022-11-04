<?php
namespace Rockndonuts\Hackqc;

use DateTime;

class NonceProvider
{
    public const NT_NAME = "hackqc_nt";

    public function getNT(): string
    {
        $exp = new DateTime("+12 hours");
        return $this->encrypt((string)($exp->getTimestamp()));
    }

    public function verify(string $value): bool
    {
        $now = new DateTime();
        $decrypted = $this->decrypt($value);

        return time() < (int)$decrypted;
    }

    public function encrypt(string $string): string
    {
        $encryption = "AES-256-CBC";
        $secretKey = 'O6uYeVZ54FSD!$#@IcqBa';
        $secretInput = 'ELLoN04$#%)FDlHsa';
        $key = hash('sha256', $secretKey);

        $iv = substr(hash('sha256', $secretInput), 0, 16);
        $output = openssl_encrypt($string, $encryption, $key, 0, $iv);

        return base64_encode($output);
    }

    public function decrypt(string $string): bool|string
    {
        $encryption = "AES-256-CBC";
        $secretKey = 'O6uYeVZ54FSD!$#@IcqBa';
        $secretInput = 'ELLoN04$#%)FDlHsa';
        $key = hash('sha256', $secretKey);

        $iv = substr(hash('sha256', $secretInput), 0, 16);

        return openssl_decrypt(base64_decode($string), $encryption, $key, 0, $iv);
    }
}