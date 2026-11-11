<?php

namespace App\Support\Attendance;

use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * Seals a guard's PIN on the phone while a sign-in waits to be uploaded.
 *
 * The sign-in page carries the server's RSA public key. When the guard presses "Sign in",
 * the browser encrypts "id|employee number|PIN" with it (WebCrypto RSA-OAEP) and keeps only the
 * sealed bytes, so a PIN sitting on a shared phone can't be read back by the next person.
 * Only the server can open it. The id and employee number inside stop a sealed PIN being
 * copied onto another sign-in.
 *
 * The key is made on first use at config('attendance.offline.key_path'). Run
 * `php artisan attendance:offline-key --rotate` to replace it (sign-ins already sealed with
 * the old key then fail and the guard is asked to sign in again).
 */
class PinSeal
{
    public static function publicKeySpki(): string
    {
        $details = openssl_pkey_get_details(self::privateKey());

        // PEM -> base64 DER (SubjectPublicKeyInfo), what WebCrypto importKey('spki') wants.
        return preg_replace('/-----[^-]+-----|\s+/', '', (string) $details['key']);
    }

    /**
     * Open a sealed PIN and check it belongs to this sign-in.
     */
    public static function open(?string $sealed, string $uuid, string $employeeNumber): ?string
    {
        $bytes = $sealed !== null ? base64_decode($sealed, true) : false;
        if ($bytes === false || $bytes === '') {
            return null;
        }
        if (! openssl_private_decrypt($bytes, $plain, self::privateKey(), OPENSSL_PKCS1_OAEP_PADDING)) {
            return null;
        }

        $parts = explode('|', (string) $plain, 3);
        if (count($parts) !== 3 || ! hash_equals($parts[0], $uuid)
            || ScanRecorder::employeeId($parts[1]) === null || ScanRecorder::employeeId($parts[1]) !== ScanRecorder::employeeId($employeeNumber)) {
            return null;
        }

        return $parts[2];
    }

    /** Seal like the browser does (tests and tools). */
    public static function seal(string $uuid, string $employeeNumber, string $pin): string
    {
        $public = openssl_pkey_get_public(openssl_pkey_get_details(self::privateKey())['key']);
        openssl_public_encrypt($uuid . '|' . $employeeNumber . '|' . $pin, $out, $public, OPENSSL_PKCS1_OAEP_PADDING);

        return base64_encode($out);
    }

    public static function generate(bool $replace = false): string
    {
        $path = (string) config('attendance.offline.key_path');
        if (File::exists($path) && ! $replace) {
            return $path;
        }
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        if (! $key || ! openssl_pkey_export($key, $pem)) {
            throw new RuntimeException('Could not make the attendance offline key (is the PHP openssl extension set up?).');
        }
        File::ensureDirectoryExists(dirname($path), 0700);
        File::put($path, $pem);
        @chmod($path, 0600);
        self::$cached = null;

        return $path;
    }

    private static ?\OpenSSLAsymmetricKey $cached = null;

    private static function privateKey(): \OpenSSLAsymmetricKey
    {
        if (self::$cached) {
            return self::$cached;
        }
        $path = self::generate();
        $key = openssl_pkey_get_private((string) File::get($path));
        if (! $key) {
            throw new RuntimeException('The attendance offline key at ' . $path . ' cannot be read.');
        }

        return self::$cached = $key;
    }
}
