<?php

declare(strict_types=1);

namespace App\Tests\Support\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;

final class TestJwtEncoder implements JWTEncoderInterface
{
    private const SECRET = 'test-secret-key-for-testing-only';

    public function encode(array $data): string
    {
        $header = $this->base64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = $this->base64UrlEncode(json_encode($data));
        $signature = $this->base64UrlEncode(
            hash_hmac('sha256', "$header.$payload", self::SECRET, true)
        );

        return "$header.$payload.$signature";
    }

    public function decode($token): array
    {
        $parts = explode('.', $token);
        if (3 !== count($parts)) {
            throw new \InvalidArgumentException('Invalid token format');
        }

        [$header, $payload, $signature] = $parts;

        $expectedSignature = $this->base64UrlEncode(
            hash_hmac('sha256', "$header.$payload", self::SECRET, true)
        );

        if (!hash_equals($expectedSignature, $signature)) {
            throw new \InvalidArgumentException('Invalid token signature');
        }

        $data = json_decode($this->base64UrlDecode($payload), true);

        if (isset($data['exp']) && $data['exp'] < time()) {
            throw new \InvalidArgumentException('Token has expired');
        }

        return $data;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($data, '-_', '+/'), true) ?: '';
    }
}
