<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Symmetric encryption for secrets stored in the database
 * (shop DB passwords, integration API credentials).
 */
final class Crypto
{
    private static function key(): string
    {
        $appKey = (string) Config::get('app_key', '');
        if ($appKey === '') {
            throw new \RuntimeException('app_key is missing from config.');
        }
        return sodium_crypto_generichash($appKey, '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
    }

    public static function encrypt(string $plain): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        return base64_encode($nonce . sodium_crypto_secretbox($plain, $nonce, self::key()));
    }

    public static function decrypt(string $payload): string
    {
        if ($payload === '') {
            return '';
        }
        $raw = base64_decode($payload, true);
        if ($raw === false || strlen($raw) < SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            return '';
        }
        $nonce = substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $plain = sodium_crypto_secretbox_open(substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), $nonce, self::key());
        return $plain === false ? '' : $plain;
    }

    public static function encryptArray(array $data): string
    {
        return self::encrypt((string) json_encode($data));
    }

    public static function decryptArray(?string $payload): array
    {
        $json = self::decrypt((string) $payload);
        $data = json_decode($json, true);
        return is_array($data) ? $data : [];
    }

    public static function token(int $bytes = 24): string
    {
        return rtrim(strtr(base64_encode(random_bytes($bytes)), '+/', '-_'), '=');
    }
}
