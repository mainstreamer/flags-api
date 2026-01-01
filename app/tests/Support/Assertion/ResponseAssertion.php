<?php

declare(strict_types=1);

namespace App\Tests\Support\Assertion;

use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Response;

final class ResponseAssertion
{
    private readonly array $json;

    public function __construct(
        private readonly Response $response,
    ) {
        $content = $this->response->getContent();
        $this->json = $content ? (json_decode($content, true) ?? []) : [];
    }

    public function assertStatus(int $expected): self
    {
        Assert::assertSame(
            $expected,
            $this->response->getStatusCode(),
            sprintf(
                'Expected status %d, got %d. Body: %s',
                $expected,
                $this->response->getStatusCode(),
                $this->response->getContent()
            )
        );

        return $this;
    }

    public function assertOk(): self
    {
        return $this->assertStatus(Response::HTTP_OK);
    }

    public function assertCreated(): self
    {
        return $this->assertStatus(Response::HTTP_CREATED);
    }

    public function assertUnauthorized(): self
    {
        return $this->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function assertForbidden(): self
    {
        return $this->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function assertNotFound(): self
    {
        return $this->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function assertUnprocessable(): self
    {
        return $this->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function assertJsonPath(string $path, mixed $expected): self
    {
        $actual = $this->getJsonPath($path);
        Assert::assertSame(
            $expected,
            $actual,
            sprintf('JSON path "%s" does not match expected value', $path)
        );

        return $this;
    }

    public function assertJsonPathExists(string $path): self
    {
        $this->getJsonPath($path); // Throws if not found

        return $this;
    }

    public function assertJsonStructure(array $structure): self
    {
        $this->assertStructureRecursive($structure, $this->json);

        return $this;
    }

    public function assertJsonCount(int $expected, ?string $path = null): self
    {
        $data = $path ? $this->getJsonPath($path) : $this->json;
        Assert::assertIsArray($data);
        Assert::assertCount($expected, $data);

        return $this;
    }

    public function getJson(): array
    {
        return $this->json;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }

    private function getJsonPath(string $path): mixed
    {
        $keys = explode('.', $path);
        $current = $this->json;

        foreach ($keys as $key) {
            if (!is_array($current) || !array_key_exists($key, $current)) {
                Assert::fail(sprintf('JSON path "%s" not found in response', $path));
            }
            $current = $current[$key];
        }

        return $current;
    }

    private function assertStructureRecursive(array $structure, array $data, string $prefix = ''): void
    {
        foreach ($structure as $key => $value) {
            $currentPath = $prefix ? "{$prefix}.{$key}" : $key;

            if (is_int($key)) {
                // Indexed array - check structure of first element
                Assert::assertIsArray($data, "Expected array at {$prefix}");
                if (!empty($data) && is_array($value)) {
                    $this->assertStructureRecursive($value, $data[0], $prefix . '[0]');
                }
            } elseif (is_array($value)) {
                // Nested structure
                Assert::assertArrayHasKey($key, $data, "Missing key: {$currentPath}");
                $this->assertStructureRecursive($value, $data[$key], $currentPath);
            } else {
                // Simple key check
                Assert::assertArrayHasKey($value, $data, "Missing key: {$prefix}.{$value}");
            }
        }
    }
}
