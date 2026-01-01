01/01/2026
Testing approach with PHPUnit and modular supporting modules

tests/
├── Unit/                          # Pure PHP, no kernel
├── Functional/                    # HTTP tests (WebTestCase)
│   ├── ApiTestCase.php           # Base with rollback + factories
│   ├── Capitals/
│   ├── Flags/
│   └── Health/
├── Acceptance/                    # End-to-end scenarios (future)
└── Support/
├── ApiClient.php
├── Assertion/
│   └── ResponseAssertion.php
├── Factory/
│   ├── UserFactory.php
│   └── GameFactory.php
├── Fixture/
│   └── FixtureLoader.php
└── Security/
└── TestJwtEncoder.php

Key features:
- No traits - explicit service composition
- Test JWT encoder replaces external OAuth in test env
- Factories with fluent API ($this->games->forEurope()->create())
- Chainable assertions (->assertOk()->assertJsonPath('key', 'value'))
- Each test wraps in transaction, rolls back in tearDown
- Three test suites - Unit, Functional, Acceptance
- Fixtures load once per suite/class, not per test

Usage with fixtures:
class GameEndpointTest extends ApiTestCase
{
protected function fixtures(): array
{
return [
UserFixture::class,
GameFixture::class,
];
}

      public function test_something(): void
      {
          // Fixtures loaded once, test creates additional data
          // All changes roll back after test
      }
}

Run commands:
vendor/bin/phpunit                       # All
vendor/bin/phpunit --testsuite=Unit      # Fast, no DB
vendor/bin/phpunit --testsuite=Functional
vendor/bin/phpunit --testsuite=Acceptance

Remark on Acceptance: Currently a placeholder. Could be used for:
- Multi-step user journey tests
- Behat if you add it later
- Tests that intentionally commit (rare cases)
