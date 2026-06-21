# Security Model

## Overview

Verify protects against two threat classes:

1. **Unauthorized feature access** - users bypassing license checks to access paid features
2. **Supply-chain tampering** - attackers modifying the verification code itself to always return a valid status (best effort, not guaranteed)

## Design Philosophy

Verify strikes a deliberate trade-off between three competing concerns:

1. **Sufficient security** — circumventing the license check should require more effort than simply sponsoring the project
2. **Development time** — the verification system should not consume disproportionate engineering effort relative to the core product
3. **Attacker effort** — the bar should be high enough to deter casual piracy, without pretending to stop a determined reverse-engineer

### Why not phone-home enforcement?

Lychee is a self-hosted photo management application. Many installations run on local networks, NAS devices, or air-gapped machines with no internet access. A verification system that requires periodic server contact would break these deployments and erode user trust.

This means we cannot revoke keys remotely. Once a hash-based key is issued, it works forever — even if the sponsor cancels. We accept this because:

- The alternative (mandatory online checks) conflicts with our self-hosted promise
- Sponsors who cancel but keep using features are a small minority
- The social contract of open-source sponsorship is more effective than DRM

### Why not stronger DRM?

Any verification that runs entirely on the user's machine can be patched out. The question is never "can it be broken?" but "is breaking it harder than paying?" For Lychee's price point and audience, the answer is yes with the current approach:

- Modifying hash validators requires finding the correct class, understanding the check, and updating the integrity hashes — multiple coordinated edits
- Forging a signature-tier license requires the private key, which never leaves the server
- Sharing a hash-based key grants no upgrade path — the key is tied to a fixed tier

### Where rotation fits

Key rotation exists to *distribute* keys conveniently, not to enforce subscription status. It solves the UX problem of users needing to manually copy keys from a sponsorship portal. The keygen server issues keys to active sponsors; if a sponsor cancels, the server stops issuing new keys, but the last-issued key remains functional. This is the intended behavior.

### Strengths and Weaknesses

| | Strengths | Weaknesses |
|---|-----------|------------|
| **Hash validation** | Works fully offline; fast (~0ms); 129-bit key space prevents brute force; simple to implement and audit | Keys are permanent and irrevocable; shared keys cannot be detected; no tier upgrade incentive once issued |
| **Signature validation** | Domain-bound (URL + email); cannot be forged without private key; supports per-installation licensing | Requires email registration; slightly more complex setup; still bypassable by patching the validator |
| **Integrity checking** | Detects casual tampering of source files; covers validators, middleware, and core logic | Does not protect the config file itself; ineffective against coordinated edits; SHA1 is not collision-resistant (acceptable here since the attacker controls the file, not just a hash) |
| **Key rotation** | Seamless key distribution; validates before persisting; retry timeout prevents abuse | Requires internet access (optional); cannot revoke previously issued keys; 1-day timeout delays recovery from transient errors |
| **Token extension** | Keeps API access alive without manual intervention; scoped permissions limit blast radius | Token in `.env` is a single point of compromise for API access; no automatic alerting on extension failure |
| **Overall model** | Low maintenance; respects offline/self-hosted use; social contract scales better than DRM at this price point | Determined attacker with filesystem access can bypass everything; cancelled sponsors retain access indefinitely |

## License Validation

### Hash-Based Validation (Supporter & Pro)

License keys are validated by comparing `hash('sha3-256', $key)` against a known hash. The hash is hardcoded in the validator class.

**Properties:**
- One-way: the license key cannot be derived from the hash
- Constant-time comparison via string equality (SHA3-256 output is fixed-length)
- No bcrypt/argon2 by design: those algorithms cost ~150ms per call, unacceptable for a check that runs on every request

**Trade-off:** SHA3-256 is fast, meaning a brute-force attack against the 25-character key space (uppercase alphanumeric, `[A-Z0-9]^25`) is *theoretically* possible. The key space gives $log_2(36^{25}) = 25 \cdot log_2(36) \approx 129$ bits of security.

**Permanence by design:** once a hash-based key is issued, it remains valid indefinitely — even if the user cancels their subscription. This is intentional. Lychee is self-hosted and must work on offline installations without phoning home. We cannot revoke hash-based keys without breaking offline use. We rely on the good will of sponsors not to abuse this. The rotation mechanism exists to *distribute* keys, not to enforce ongoing subscription status.

### Signature-Based Validation (Signature Edition)

License keys are ECDSA signatures over a JSON payload containing the instance URL and registered email:

```json
{"url": "https://example.com", "email": "user@example.com"}
```

**Properties:**
- Asymmetric: only the private key (held server-side) can produce valid signatures
- Domain-bound: a valid signature for one installation is invalid for another (different URL or email)
- Algorithm: ECDSA with SHA-256 over the NIST P-256 curve
- The public key is embedded in the validator source

**Implication:** compromising the public key alone does not allow forging licenses. An attacker would need the private key.

## Integrity Validation

Core source files are protected by SHA1 checksums stored in `config/verify.php`. The `Verify::validate()` method reads each listed class file, normalizes line endings, and compares the SHA1 hash against the stored value.

**What it detects:**
- Modified validator logic (e.g., making `validate()` always return true)
- Modified status resolution (e.g., hardcoding `SIGNATURE_EDITION`)
- Modified middleware (e.g., removing the authorization check)

**What it does NOT detect:**
- Modifications to files not in the validation list (e.g., `Rotation.php`, application code)
- Replacement of the config file itself with altered hashes
- Runtime monkey-patching or class aliasing

**Threat model:** this is a deterrent against casual tampering, not a defense against a determined attacker with filesystem access. A determined attacker can replace both the source files and the config hashes simultaneously.

## Key Rotation Security

The rotation mechanism (`Rotation.php`) fetches a new license key from a remote keygen server.

### Authentication

Requests to the keygen API are authenticated with a Bearer token (`KEYGEN_API_KEY`). This token:
- Is stored in the `.env` file, never in source control
- Has scoped permissions (`licenses:read`) on the keygen server
- Has a configurable expiration, extendable via `TokenExtension`

### Validation Before Persistence

A fetched key is **never written to the database without local validation**. The rotation flow:

1. Fetch the key from the keygen server
2. Instantiate a `Verify` instance (via `VerifyFactory`) with the fetched key
3. Call `get_status()` — if it resolves to `FREE_EDITION`, the key is rejected
4. Only on successful validation is the key persisted to the `configs` table

This prevents a compromised or misbehaving keygen server from injecting an invalid key that would downgrade the installation.

### Retry Timeout

On failure (HTTP error, authentication failure, invalid key), a 1-day cache entry is set to prevent repeated requests. This limits:
- Exposure to rate limiting or account lockout on the keygen server
- Unnecessary network traffic from misconfigured installations

### Token Extension

The `TokenExtension` class extends the API token's lifetime via `PATCH /tokens/extend`. This is a separate concern from license validation — it maintains access to the keygen API itself. A failed extension does not affect the current license status.

## Sensitive Data Handling

- Constructor parameters for keys, emails, and hashes are annotated with `#[\SensitiveParameter]` to prevent them from appearing in stack traces
- The `RotationResult` DTO intentionally does not expose the fetched license key
- The `configs` table marks license keys with `is_secret = true`

## Trust Boundaries

| Boundary | Trust Level |
|----------|-------------|
| Validator source code | Trusted (integrity-checked) |
| `config/verify.php` | Trusted (but not self-protected) |
| Database `configs` table | Trusted (application-controlled) |
| Keygen server responses | Untrusted (validated before use) |
| `.env` / environment variables | Trusted (server-operator controlled) |
| Client HTTP requests | Untrusted (middleware enforces status checks) |
