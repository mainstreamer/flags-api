# JWKS (JSON Web Key Set) - How It Works

## Why JWKS Is Needed

This API validates JWT tokens issued by an external authorization server (`auth.izeebot.top`). The auth server publishes its public keys at a standard JWKS endpoint, allowing this API to verify token signatures without sharing secrets.

## Why Custom JWT Decoding?

Lexik JWT bundle handles the authentication framework, but its default encoder expects a **static key file**. We need:

1. **Dynamic key fetching** - Get public key from auth server's `/.well-known/jwks.json` endpoint
2. **Automatic key rotation** - If the auth server rotates keys, the custom encoder detects signature failure and automatically fetches the new key

Without this, key rotation would break authentication until someone manually updates the key file.

## Architecture Flow

```
┌─────────────┐      1. Login       ┌─────────────────┐
│   Client    │ ─────────────────►  │   Auth Server   │
│             │ ◄─────────────────  │  izeebot.top    │
└─────────────┘      2. JWT Token   └─────────────────┘
       │                                    │
       │ 3. Request + JWT                   │
       ▼                                    │
┌─────────────────────────────────┐         │
│           Flags API             │         │
├─────────────────────────────────┤         │
│  Lexik JWT Bundle               │         │
│  • Extracts token from header   │         │
│  • Manages firewall/auth flow   │         │
│  • Loads user from database     │         │
├─────────────────────────────────┤         │
│  JwksJwtEncoder (custom)        │         │
│  • Parses JWT structure         │         │
│  • Verifies RS256 signature     │         │
│  • Handles key rotation         │         │
├─────────────────────────────────┤         │
│  JwksService                    │  4. Fetch JWKS
│  • Fetches key from endpoint  ◄───────────┘
│  • Converts JWK → PEM format    │  /.well-known/jwks.json
│  • Caches key (1 hour)          │
└─────────────────────────────────┘
```

## Token Validation Flow

```
        Request with JWT arrives
                  │
                  ▼
    ┌───────────────────────────┐
    │   Lexik JWT Authenticator │
    │   extracts token from     │
    │   Authorization header    │
    └─────────────┬─────────────┘
                  │
                  ▼
    ┌───────────────────────────┐
    │   JwksJwtEncoder.decode() │
    └─────────────┬─────────────┘
                  │
                  ▼
    ┌───────────────────────────┐
    │  Get public key from      │
    │  cache (or fetch if miss) │
    └─────────────┬─────────────┘
                  │
                  ▼
    ┌───────────────────────────┐
    │  Parse JWT:               │
    │  header.payload.signature │
    └─────────────┬─────────────┘
                  │
                  ▼
    ┌───────────────────────────┐
    │  Verify RS256 signature   │
    │  using OpenSSL            │
    └─────────────┬─────────────┘
                  │
         ┌───────┴───────┐
         │               │
      Valid?          Invalid
         │               │
         │               ▼
         │    ┌───────────────────┐
         │    │ Refresh key from  │
         │    │ JWKS & retry once │
         │    │ (handles rotation)│
         │    └─────────┬─────────┘
         │              │
         │       ┌──────┴──────┐
         │       │             │
         │    Valid?       Still Invalid
         │       │             │
         ▼       ▼             ▼
    ┌─────────────────┐   ┌─────────────┐
    │ Check exp claim │   │ Reject      │
    └────────┬────────┘   └─────────────┘
             │
             ▼
    ┌─────────────────┐
    │ Return payload  │
    │ to Lexik        │
    └────────┬────────┘
             │
             ▼
    ┌─────────────────┐
    │ Lexik loads     │
    │ user by 'sub'   │
    └─────────────────┘
```

## Key Components

| Component | Role |
|-----------|------|
| **Lexik JWT Bundle** | Auth framework - firewall, token extraction, user loading |
| **JwksJwtEncoder** | JWT parsing & signature verification (plugs into Lexik) |
| **JwksService** | Fetches JWKS, converts JWK→PEM, caches key |

## JwksService Details

Converts JWK (JSON Web Key) format to PEM format that OpenSSL can use:

```
JWKS Endpoint Response          JwksService Output
─────────────────────────       ──────────────────────
{                               -----BEGIN PUBLIC KEY-----
  "keys": [{                    MIIBIjANBgkqhkiG9w0BAQEFAA...
    "kty": "RSA",         →     ...base64 encoded DER...
    "n": "base64url...",        -----END PUBLIC KEY-----
    "e": "AQAB",
    "use": "sig"
  }]
}
```

## Configuration

### Environment Variable

```bash
JWKS_URI=https://auth.izeebot.top/.well-known/jwks.json
```

### Lexik Configuration

```yaml
# config/packages/lexik_jwt_authentication.yaml
lexik_jwt_authentication:
    public_key: '%kernel.project_dir%/config/jwt/public.pem'  # fallback, not actively used
    user_id_claim: sub
    encoder:
        service: App\Flags\Security\JwksJwtEncoder
```

### Security Firewall

```yaml
# config/packages/secrity.yaml
api:
    pattern: ^/api
    stateless: true
    provider: database_provider
    jwt: ~   # ← Lexik handles auth, calls our custom encoder
```

## Warmup Command

Pre-populate the key cache during deployment:

```bash
php bin/console app:jwks:warmup
```

## Notes

- The `public.pem` file is written by `JwksService` but the custom encoder uses the cached key directly - the file is effectively a backup/debug artifact
- Only RS256 algorithm is accepted
- Key cache TTL is 1 hour
