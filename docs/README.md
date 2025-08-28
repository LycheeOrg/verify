# Verify - Architecture Documentation

Verify is the License management system used by Lychee to validate license keys. It provides a flexible architecture for validating licenses through either hash-based validation or cryptographic signature validation.

## Core Architecture

### Overall Structure

```
src/
├── Contract/                   # Core interfaces and enums
│   ├── Status.php              # License status types
│   ├── ValidatorInterface.php  # Validator contract
│   └── VerifyInterface.php     # Core verification contract
├── Exceptions/                 # Custom exceptions
│   ├── BaseVerifyException.php
│   └── SupporterOnlyOperationException.php
├── Facades/                    # Laravel facades
│   └── VerifyFacade.php
├── Http/                       # HTTP components
│   └── Middleware/             # Middleware for route protection
├── Validators/                 # License validation implementations
│   ├── ValidateHash.php        # Hash-based validation
│   └── ValidateSignature.php   # Cryptographic signature validation
├── Verify.php                  # Main verification class
└── VerifyServiceProvider.php   # Laravel service provider
```

### Component Description

#### 1. Status System

The license status is represented by the `Status` enum, which defines three levels:

- `FREE_EDITION` - Basic features, no license required
- `SUPPORTER_EDITION` - Standard supporter features
- `PLUS_EDITION` - Premium features with extended capabilities

#### 2. Validation Mechanism

The plugin implements a strategy pattern for license validation through the `ValidatorInterface`:

```php
interface ValidatorInterface
{
    public function validate(string $verifiable, string $license): bool;
    public function grant(): Status;
}
```

Two concrete validators are provided:

1. **ValidateHash** - Uses password hashing to validate a static license key
   - Returns `SUPPORTER_EDITION` status when valid
   - Simple validation mechanism for standard supporters

2. **ValidateSignature** - Uses cryptographic signatures for validation
   - Returns `PLUS_EDITION` status when valid
   - More secure mechanism for premium users
   - Uses asymmetric cryptography (ECDSA) for verification

#### 3. Core Verify Class

The `Verify` class serves as the central hub and implements `VerifyInterface`. It:

- Coordinates between different validators
- Provides convenience methods for checking license status
- Handles authorization logic
- Provides conditional execution based on license status

#### 4. Service Provider & Integration

The `VerifyServiceProvider` handles Laravel integration by:
- Registering the Verify service in the Laravel container
- Merging configuration files
- Making the service available through the facade

#### 5. Facade

The `VerifyFacade` provides a static interface to access the Verify functionality, following the Laravel facade pattern.

## Usage Examples

### Basic Verification

```php
// Check if the user is a supporter
if ($verify->is_supporter()) {
    // Provide supporter features
}

// Check if the user has premium status
if ($verify->is_plus()) {
    // Provide premium features
}
```

### Authorization

```php
// Will throw SupporterOnlyOperationException if not a supporter
$verify->authorize();

// Will throw exception if not a plus user
$verify->authorize(Status::PLUS_EDITION);
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

- Validation configuration for integrity checks
- Public keys and hash settings (in the actual implementation)

## Database Integration

The plugin uses the database to store:
- License keys
- User emails associated with licenses

These are stored in the `configs` table with dedicated migration files.

## Security Considerations

The plugin implements multiple layers of security:
1. Hash-based validation for supporter licenses
2. Cryptographic signature validation for premium licenses
3. Server-side validation to prevent client-side tampering
4. Integrity checking of core verification files

## Extension Points

The architecture allows extending the system by:
1. Creating new validators implementing `ValidatorInterface`
2. Adding additional status levels to the `Status` enum
3. Creating custom middleware for specific route protection

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
