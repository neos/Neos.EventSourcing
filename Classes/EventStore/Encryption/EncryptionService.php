<?php

namespace Neos\EventSourcing\EventStore\Encryption;

use Exception;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
class EncryptionService
{
    /**
     * Encrypts the given data using the configured encryption method and returns a string
     * containing the construction name and the base64-encoded nonce and encrypted data.
     *
     * @param string $data Data to encrypt
     * @param string $encryptionKey The (binary) encryption key
     * @return string Encoded, encrypted data, suitable for storage (e.g. in the database)
     * @throws Exception
     */
    public function encryptAndEncode(string $data, string $encryptionKey): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_NPUBBYTES);
        $encryptedData = sodium_crypto_aead_chacha20poly1305_ietf_encrypt(
            $data,
            $nonce,
            $nonce,
            $encryptionKey
        );

        return 'ChaCha20-Poly1305-IETF$' . base64_encode($nonce) . '$' . base64_encode($encryptedData);
    }

    /**
     * Decrypts the given encoded and encrypted data using the configured encryption method
     * and returns the decrypted data.
     *
     * @param string $encodedAndEncryptedData The data originally created by encryptAndEncode()
     * @param string $encryptionKey The (binary) encryption key
     * @return string Decrypted data
     */
    public function decodeAndDecrypt(string $encodedAndEncryptedData, string $encryptionKey): string
    {
        [$construction, $encodedNonce, $encodedEncryptedSerializedAccessToken] = explode('$', $encodedAndEncryptedData);
        if ($construction !== 'ChaCha20-Poly1305-IETF') {
            throw new \RuntimeException(sprintf('Failed decrypting serialized access token: unsupported AEAD construction "%s"', $construction), 1604938723);
        }

        $nonce = base64_decode($encodedNonce);
        return sodium_crypto_aead_chacha20poly1305_ietf_decrypt(
            base64_decode($encodedEncryptedSerializedAccessToken),
            $nonce,
            $nonce,
            $encryptionKey
        );
    }

    /**
     * @return string
     * @throws Exception
     */
    public static function generateEncryptionKey(): string
    {
        return sodium_crypto_aead_chacha20poly1305_ietf_keygen();
    }

}
