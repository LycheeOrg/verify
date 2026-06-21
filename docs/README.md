# Verify - Architecture Documentation

Verify is the License management system used by Lychee to validate license keys. It provides a flexible architecture for validating licenses through either hash-based validation or cryptographic signature validation, with support for automatic key rotation via a remote keygen server.

## Core Architecture

### Overall Structure

```
src/
├── Contract/                        # Core interfaces and enums
│   ├── Status.php                   # License status types
│   ├── ValidatorInterface.php       # Validator contract
│   ├── VerifyInterface.php          # Core verification contract
│   ├── VerifyFactory.php            # Factory interface for creating Verify instances
│   ├── RotationResult.php           # DTO for rotation outcomes
│   └── TokenExtensionResult.php     # DTO for token extension outcomes
├── Exceptions/                      # Custom exceptions
│   ├── BaseVerifyException.php
│   └── SupporterOnlyOperationException.php
├── Facades/                         # Laravel facades
│   └── VerifyFacade.php
├── Http/                            # HTTP components
│   └── Middleware/                  # Middleware for route protection
├── Validators/                      # License validation implementations
│   ├── ValidateSupporter.php        # SHA3-256 hash validation (Supporter)
│   ├── ValidatePro.php              # SHA3-256 hash validation (Pro)
│   └── ValidateSignature.php        # ECDSA signature validation (Signature)
├── DefaultVerifyFactory.php         # Default factory implementation
├── Rotation.php                     # Automatic key rotation via keygen API
├── TokenExtension.php               # API token lifetime extension
├── Verify.php                       # Main verification class
├── VerifyTrait.php                  # Shared status-checking logic
└── VerifyServiceProvider.php        # Laravel service provider
```

### Component Description

#### 1. Status System

The license status is represented by the `Status` enum, which defines four levels:

- `FREE_EDITION` - Basic features, no license required
- `SUPPORTER_EDITION` - Standard supporter features
- `PRO_EDITION` - Premium features with extended capabilities
- `SIGNATURE_EDITION` - Highest tier, cryptographically signed licenses

#### 2. Validation Mechanism

The plugin implements a strategy pattern for license validation through the `ValidatorInterface`:

```php
interface ValidatorInterface
{
    public function validate(string $verifiable, string $license): bool;
    public function grant(): Status;
}
```

Three concrete validators are provided:

1. **ValidateSupporter** - Uses SHA3-256 hashing to validate a static license key
   - Returns `SUPPORTER_EDITION` status when valid

2. **ValidatePro** - Uses SHA3-256 hashing to validate a static license key
   - Returns `PRO_EDITION` status when valid

3. **ValidateSignature** - Uses ECDSA cryptographic signatures for validation
   - Returns `SIGNATURE_EDITION` status when valid
   - Uses asymmetric cryptography (ECDSA with SHA-256) for verification

#### 3. Core Verify Class

The `Verify` class serves as the central hub and implements `VerifyInterface`. It:

- Coordinates between different validators
- Provides convenience methods for checking license status
- Handles authorization logic
- Provides conditional execution based on license status
- Supports delayed initialization (safe when database is not yet available)

#### 4. Key Rotation

The `Rotation` class provides automatic license key fetching from a remote keygen server:

- Queries `GET /licenses/me` on the configured keygen server
- Validates the fetched key locally using the existing validators (via `VerifyFactory`) before persisting
- Implements a 1-day retry timeout (via Laravel Cache) to avoid hammering the server
- Returns a `RotationResult` DTO indicating success or failure with a reason message

```php
$factory = new DefaultVerifyFactory();
$rotation = new Rotation($factory);
$result = $rotation->rotate();

if ($result->success) {
    // Key was updated in the database
} else {
    // $result->message explains why (e.g., "Unauthenticated.", "Retry timeout active.")
}
```

#### 5. Token Extension

The `TokenExtension` class extends the lifetime of the keygen API token:

- Calls `PATCH /tokens/extend` on the keygen server
- Returns a `TokenExtensionResult` DTO with token information on success (id, name, scopes, expires_at as Carbon)

```php
$extension = new TokenExtension();
$result = $extension->extend();

if ($result->success) {
    // $result->expires_at is a Carbon instance with the new expiration
}
```

#### 6. Service Provider & Integration

The `VerifyServiceProvider` handles Laravel integration by:
- Registering the Verify service in the Laravel container
- Merging configuration files
- Making the service available through the facade

#### 7. Facade

The `VerifyFacade` provides a static interface to access the Verify functionality, following the Laravel facade pattern.

## Usage Examples

### Basic Verification

```php
// Check if the user is a supporter
if ($verify->is_supporter()) {
    // Provide supporter features
}

// Check if the user has pro status
if ($verify->is_pro()) {
    // Provide pro features
}

// Check if the user has signature status
if ($verify->is_signature()) {
    // Provide signature features
}
```

### Authorization

```php
// Will throw SupporterOnlyOperationException if not a supporter
$verify->authorize();

// Will throw exception if not a pro user
$verify->authorize(Status::PRO_EDITION);
```

### Conditional Execution

```php
$result = $verify->when(
    fn() => 'Supporter feature enabled!',
    fn() => 'Please upgrade to supporter edition.',
    Status::SUPPORTER_EDITION
);
```

## Configuration

Configuration is stored in `config/verify.php`, which includes:

- `keygen_api_key` - API key for the keygen server (from `KEYGEN_API_KEY` env var)
- `keygen_url` - Base URL of the keygen server (from `KEYGEN_URL` env var, defaults to `https://keygen.lycheeorg.dev/api`)
- `validation` - SHA1 hashes for integrity checks of core files

## Database Integration

The plugin uses the database to store:
- License keys
- User emails associated with licenses

These are stored in the `configs` table with dedicated migration files.

## Security Considerations

The plugin implements multiple layers of security:
1. SHA3-256 hash validation for supporter and pro licenses
2. ECDSA cryptographic signature validation for signature-tier licenses
3. Server-side validation to prevent client-side tampering
4. Integrity checking of core verification files
5. Remote key rotation validates fetched keys locally before persisting
6. Sensitive parameters are marked with `#[\SensitiveParameter]`

## Extension Points

The architecture allows extending the system by:
1. Creating new validators implementing `ValidatorInterface`
2. Adding additional status levels to the `Status` enum
3. Creating custom middleware for specific route protection
4. Implementing custom `VerifyFactory` for specialized key validation

## Integrity Validation

The system includes a self-validation mechanism that verifies the integrity of core verification files to detect tampering:

```php
if ($verify->validate()) {
    // System is intact
} else {
    // Core files have been tampered with
}
```

This system compares file checksums against expected values to ensure the verification system itself hasn't been compromised.

To update the hashes after modifying core files:

```bash
php update-hashes.php
```
