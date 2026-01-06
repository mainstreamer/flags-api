<?php

declare(strict_types=1);

namespace App\Flags\Security;

use App\Flags\Service\JwksService;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;

readonly class JwksJwtEncoder implements JWTEncoderInterface
{
    public function __construct(
        private JwksService $jwksService,
        private ?LoggerInterface $logger = null,
    ) {
    }

    public function encode(array $data): string
    {
        throw new \LogicException('This application does not issue tokens. Use the auth server.');
    }

    /**
     * @throws InvalidArgumentException
     * @throws JWTDecodeFailureException
     * @throws \JsonException
     */
    public function decode($token): array
    {
        $this->logger?->notice('JwksJwtEncoder: CUSTOM ENCODER ACTIVE - decoding token');

        $publicKey = $this->jwksService->getPublicKey();

        if (!$publicKey) {
            $this->logger?->error(
                'JwksJwtEncoder: Unable to fetch public key from JWKS endpoint',
            );

            throw new JWTDecodeFailureException(JWTDecodeFailureException::INVALID_TOKEN, 'Unable to fetch public key from JWKS endpoint');
        }

        $this->logger?->debug('JwksJwtEncoder: Public key fetched successfully');

        $parts = explode('.', $token);
        if (3 !== \count($parts)) {
            throw new JWTDecodeFailureException(JWTDecodeFailureException::INVALID_TOKEN, 'Invalid token format');
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;

        // Decode header
        $header = json_decode($this->base64UrlDecode($headerB64), true, 512, JSON_THROW_ON_ERROR);
        if (!$header || ($header['alg'] ?? '') !== 'RS256') {
            throw new JWTDecodeFailureException(JWTDecodeFailureException::INVALID_TOKEN, 'Unsupported algorithm or invalid header');
        }

        // Verify signature
        $dataToVerify = $headerB64 . '.' . $payloadB64;
        $signature = $this->base64UrlDecode($signatureB64);

        $publicKeyResource = openssl_pkey_get_public($publicKey);
        if (!$publicKeyResource) {
            throw new JWTDecodeFailureException(JWTDecodeFailureException::INVALID_TOKEN, 'Invalid public key');
        }

        $isValid = openssl_verify($dataToVerify, $signature, $publicKeyResource, OPENSSL_ALGO_SHA256);

        if (1 !== $isValid) {
            // Try refreshing the key in case it was rotated
            $publicKey = $this->jwksService->refreshPublicKey();
            if ($publicKey) {
                $publicKeyResource = openssl_pkey_get_public($publicKey);
                if ($publicKeyResource) {
                    $isValid = openssl_verify(
                        $dataToVerify,
                        $signature,
                        $publicKeyResource,
                        OPENSSL_ALGO_SHA256
                    );
                }
            }

            if (1 !== $isValid) {
                throw new JWTDecodeFailureException(JWTDecodeFailureException::INVALID_TOKEN, 'Invalid token signature');
            }
        }

        // Decode payload
        $payload = json_decode(
            $this->base64UrlDecode($payloadB64),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        if (!$payload) {
            throw new JWTDecodeFailureException(JWTDecodeFailureException::INVALID_TOKEN, 'Invalid payload');
        }

        // Check expiration
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            $this->logger?->warning('JwksJwtEncoder: Token has expired', ['exp' => $payload['exp']]);
            throw new JWTDecodeFailureException(JWTDecodeFailureException::EXPIRED_TOKEN, 'Token has expired');
        }

        $this->logger?->notice('JwksJwtEncoder: CUSTOM ENCODER - Token verified successfully', [
            'sub' => $payload['sub'] ?? 'missing',
        ]);

        return $payload;
    }

    private function base64UrlDecode(string $data): string
    {
        $remainder = \strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($data, '-_', '+/'), true) ?: '';
    }
}
