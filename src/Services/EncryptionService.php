<?php

namespace OWCSignicatOpenID\Services;

/**
 * Exit when accessed directly.
 */
if (! defined('ABSPATH')) {
    exit;
}

use OWCSignicatOpenID\Interfaces\Services\EncryptionServiceInterface;

class EncryptionService implements EncryptionServiceInterface
{
    private const KEY_OPTION_NAME = 'owc_signicat_openid_encryption_key';
    private const NONCE_LENGTH = 24;

    public function encrypt(string $content): string
    {
        $nonce = random_bytes(self::NONCE_LENGTH);

        return base64_encode(
            $nonce . sodium_crypto_aead_xchacha20poly1305_ietf_encrypt(
                $content,
                $nonce,
                $nonce,
                $this->getKey()
            )
        );
    }

    public function decrypt(string $content): string
    {
        $decoded = base64_decode($content);
        $nonce = substr($decoded, 0, self::NONCE_LENGTH);
        $ciphertext = substr($decoded, self::NONCE_LENGTH);

        return sodium_crypto_aead_xchacha20poly1305_ietf_decrypt(
            $ciphertext,
            $nonce,
            $nonce,
            $this->getKey()
        );
    }

    private function getKey(): string
    {
        $key = get_option(self::KEY_OPTION_NAME);
        if (empty($key)) {
            $key = wp_generate_password(64);
            update_option(self::KEY_OPTION_NAME, $key);
        }

        return $key;
    }
}
