<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Flags\Entity\User;
use App\Tests\Support\Assertion\ResponseAssertion;
use App\Tests\Support\Factory\UserFactory;
use App\Tests\Support\Security\TestJwtEncoder;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class ApiClient
{
    private ?User $authenticatedUser = null;
    private readonly TestJwtEncoder $jwtEncoder;

    public function __construct(
        private readonly KernelBrowser $browser,
        private readonly UserFactory $userFactory,
    ) {
        $this->jwtEncoder = new TestJwtEncoder();
        $this->browser->setServerParameter('CONTENT_TYPE', 'application/json');
        $this->browser->setServerParameter('HTTP_ACCEPT', 'application/json');
    }

    public function asGuest(): self
    {
        $this->authenticatedUser = null;
        $this->browser->setServerParameter('HTTP_AUTHORIZATION', '');

        return $this;
    }

    public function asUser(?User $user = null): self
    {
        $this->authenticatedUser = $user ?? $this->userFactory->create();
        $token = $this->generateJwtToken($this->authenticatedUser);
        $this->browser->setServerParameter('HTTP_AUTHORIZATION', "Bearer $token");

        return $this;
    }

    public function asNewUser(): self
    {
        return $this->asUser($this->userFactory->create());
    }

    public function getUser(): ?User
    {
        return $this->authenticatedUser;
    }

    public function get(string $uri, array $parameters = []): ResponseAssertion
    {
        return $this->request('GET', $uri, $parameters);
    }

    public function post(string $uri, array $data = []): ResponseAssertion
    {
        return $this->request('POST', $uri, $data);
    }

    public function put(string $uri, array $data = []): ResponseAssertion
    {
        return $this->request('PUT', $uri, $data);
    }

    public function patch(string $uri, array $data = []): ResponseAssertion
    {
        return $this->request('PATCH', $uri, $data);
    }

    public function delete(string $uri): ResponseAssertion
    {
        return $this->request('DELETE', $uri);
    }

    private function request(string $method, string $uri, array $data = []): ResponseAssertion
    {
        $content = in_array($method, ['POST', 'PUT', 'PATCH']) ? json_encode($data) : null;
        $parameters = 'GET' === $method ? $data : [];

        $this->browser->request($method, $uri, $parameters, [], [], $content);

        return new ResponseAssertion($this->browser->getResponse());
    }

    private function generateJwtToken(User $user): string
    {
        return $this->jwtEncoder->encode([
            'sub' => $user->getSub(),
            'exp' => time() + 3600,
            'iat' => time(),
        ]);
    }
}
