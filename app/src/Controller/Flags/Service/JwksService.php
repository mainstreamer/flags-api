<?php

declare(strict_types=1);

namespace App\Flags\Service;

use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class JwksService
{
    private const string CACHE_KEY = 'jwks_public_key';
    private const int CACHE_TTL = 3600; // 1 hour
    private const string PUBLIC_KEY_PATH = '/config/jwt/public.pem';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache,
        private readonly string $jwksUri,
        private readonly string $projectDir,
    ) {
    }

    public function getPublicKey(): ?string
    {
        return $this->cache->get(self::CACHE_KEY, function (ItemInterface $item): ?string {
            $item->expiresAfter(self::CACHE_TTL);

            $key = $this->fetchPublicKeyFromJwks();
            if ($key) {
                $this->savePublicKeyToFile($key);
            }

            return $key;
        });
    }

    /**
     * @throws InvalidArgumentException
     */
    public function refreshPublicKey(): ?string
    {
        $this->cache->delete(self::CACHE_KEY);

        return $this->getPublicKey();
    }

    private function savePublicKeyToFile(string $publicKey): void
    {
        $path = $this->projectDir . self::PUBLIC_KEY_PATH;
        $dir = \dirname($path);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, $publicKey);
    }

    private function fetchPublicKeyFromJwks(): ?string
    {
        try {
            $response = $this->httpClient->request('GET', $this->jwksUri);
            $jwks = $response->toArray();

            if (empty($jwks['keys'])) {
                return null;
            }

            // Get the first RSA key for signing
            foreach ($jwks['keys'] as $key) {
                if (($key['kty'] ?? '') === 'RSA' && ($key['use'] ?? 'sig') === 'sig') {
                    return $this->jwkToPem($key);
                }
            }

            return null;
        } catch (\Throwable $e) {
            // Log error in production
            return null;
        }
    }

    private function jwkToPem(array $jwk): string
    {
        $n = $this->base64UrlDecode($jwk['n']);
        $e = $this->base64UrlDecode($jwk['e']);

        $modulus = $this->encodeLength(\strlen($n)) . $n;
        $publicExponent = $this->encodeLength(\strlen($e)) . $e;

        $rsaPublicKey = "\x30" . $this->encodeLength(\strlen($modulus) + \strlen($publicExponent) + 2)
            . "\x02" . $modulus
            . "\x02" . $publicExponent;

        $rsaOID = "\x30\x0d\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01\x05\x00";
        $publicKeyBitString = "\x00" . $rsaPublicKey;
        $publicKeyBitString = "\x03" . $this->encodeLength(\strlen($publicKeyBitString)) . $publicKeyBitString;

        $rsaPublicKeySequence = $rsaOID . $publicKeyBitString;
        $rsaPublicKeySequence = "\x30" . $this->encodeLength(\strlen($rsaPublicKeySequence)) . $rsaPublicKeySequence;

        return "-----BEGIN PUBLIC KEY-----\n"
            . chunk_split(base64_encode($rsaPublicKeySequence), 64, "\n")
            . '-----END PUBLIC KEY-----';
    }

    private function base64UrlDecode(string $data): string
    {
        $remainder = \strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($data, '-_', '+/'), true) ?: '';
    }

    private function encodeLength(int $length): string
    {
        if ($length <= 0x7F) {
            return \chr($length);
        }

        $temp = ltrim(pack('N', $length), "\x00");

        return \chr(0x80 | \strlen($temp)) . $temp;
    }
}
