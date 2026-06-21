# Lychee verification

License management package for Lychee. Validates license keys locally via SHA3-256 hashes and ECDSA signatures, with support for automatic key rotation from a remote keygen server.

## Features

- Multi-tier license validation (Free, Supporter, Pro, Signature)
- Automatic key rotation via keygen API
- API token lifetime extension
- Laravel middleware for route protection
- File integrity self-validation

## Configuration

Set the following environment variables to enable key rotation and token extension:

```env
KEYGEN_API_KEY=your-api-key
KEYGEN_URL=https://keygen.lycheeorg.dev/api
```

## Testing

```bash
composer test
```

## Static Analysis

```bash
composer analyse
```

## Updating Integrity Hashes

After modifying core source files, update the validation hashes:

```bash
php update-hashes.php
```

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [LycheeOrg](https://github.com/LycheeOrg)
