<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Flags\Security\JwksJwtEncoder;
use App\Flags\Service\JwksService;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use PHPUnit\Framework\TestCase;

class JwksJwtEncoderTest extends TestCase
{
    private string $privateKey = '';
    private string $publicKey = '';

    protected function setUp(): void
    {
        // Generate a test RSA key pair
        $config = [
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];
        $keyResource = openssl_pkey_new($config);
        $privateKey = '';
        openssl_pkey_export($keyResource, $privateKey);
        $this->privateKey = $privateKey;
        $keyDetails = openssl_pkey_get_details($keyResource);
        $this->publicKey = $keyDetails['key'];
    }

    public function testDecodeValidToken(): void
    {
        $jwksService = $this->createMock(JwksService::class);
        $jwksService->method('getPublicKey')->willReturn($this->publicKey);

        $encoder = new JwksJwtEncoder($jwksService);

        $token = $this->createToken([
            'sub' => '123',
            'exp' => time() + 3600,
            'iat' => time(),
        ]);

        $payload = $encoder->decode($token);

        $this->assertEquals('123', $payload['sub']);
    }

    public function testDecodeExpiredToken(): void
    {
        $jwksService = $this->createMock(JwksService::class);
        $jwksService->method('getPublicKey')->willReturn($this->publicKey);

        $encoder = new JwksJwtEncoder($jwksService);

        $token = $this->createToken([
            'sub' => '123',
            'exp' => time() - 3600, // Expired
            'iat' => time() - 7200,
        ]);

        $this->expectException(JWTDecodeFailureException::class);
        $this->expectExceptionMessage('Token has expired');

        $encoder->decode($token);
    }

    public function testDecodeInvalidSignature(): void
    {
        // Generate a different key pair for signing
        $config = [
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];
        $differentKeyResource = openssl_pkey_new($config);
        openssl_pkey_export($differentKeyResource, $differentPrivateKey);

        $jwksService = $this->createMock(JwksService::class);
        $jwksService->method('getPublicKey')->willReturn($this->publicKey);
        $jwksService->method('refreshPublicKey')->willReturn($this->publicKey);

        $encoder = new JwksJwtEncoder($jwksService);

        // Create token with different private key
        $token = $this->createToken([
            'sub' => '123',
            'exp' => time() + 3600,
        ], $differentPrivateKey);

        $this->expectException(JWTDecodeFailureException::class);
        $this->expectExceptionMessage('Invalid token signature');

        $encoder->decode($token);
    }

    public function testDecodeInvalidTokenFormat(): void
    {
        $jwksService = $this->createMock(JwksService::class);
        $jwksService->method('getPublicKey')->willReturn($this->publicKey);

        $encoder = new JwksJwtEncoder($jwksService);

        $this->expectException(JWTDecodeFailureException::class);
        $this->expectExceptionMessage('Invalid token format');

        $encoder->decode('invalid.token');
    }

    public function testDecodeNoPublicKey(): void
    {
        $jwksService = $this->createMock(JwksService::class);
        $jwksService->method('getPublicKey')->willReturn(null);

        $encoder = new JwksJwtEncoder($jwksService);

        $this->expectException(JWTDecodeFailureException::class);
        $this->expectExceptionMessage('Unable to fetch public key');

        $encoder->decode('any.token.here');
    }

    public function testEncodeThrowsException(): void
    {
        $jwksService = $this->createMock(JwksService::class);
        $encoder = new JwksJwtEncoder($jwksService);

        $this->expectException(\LogicException::class);

        $encoder->encode(['sub' => '123']);
    }

    private function createToken(array $payload, ?string $privateKey = null): string
    {
        $privateKey = $privateKey ?? $this->privateKey;

        $header = [
            'typ' => 'JWT',
            'alg' => 'RS256',
        ];

        $headerB64 = $this->base64UrlEncode(json_encode($header));
        $payloadB64 = $this->base64UrlEncode(json_encode($payload));

        $dataToSign = $headerB64 . '.' . $payloadB64;

        openssl_sign($dataToSign, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        $signatureB64 = $this->base64UrlEncode($signature);

        return $headerB64 . '.' . $payloadB64 . '.' . $signatureB64;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
