# MCP Error Codes

[![CI](https://github.com/code-wheel/mcp-error-codes/actions/workflows/ci.yml/badge.svg)](https://github.com/code-wheel/mcp-error-codes/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/code-wheel/mcp-error-codes/graph/badge.svg)](https://codecov.io/gh/code-wheel/mcp-error-codes)
[![Latest Stable Version](https://poser.pugx.org/code-wheel/mcp-error-codes/v)](https://packagist.org/packages/code-wheel/mcp-error-codes)
[![PHP Version](https://img.shields.io/packagist/php-v/code-wheel/mcp-error-codes.svg)](https://packagist.org/packages/code-wheel/mcp-error-codes)
[![License](https://poser.pugx.org/code-wheel/mcp-error-codes/license)](https://packagist.org/packages/code-wheel/mcp-error-codes)

Standardized error codes for MCP (Model Context Protocol) servers in PHP.

**Zero dependencies** - pure PHP 8.1+.

## Installation

```bash
composer require code-wheel/mcp-error-codes
```

## Usage

```php
use CodeWheel\McpErrorCodes\ErrorCode;

// Use constants for consistent error responses
$response = [
    'success' => false,
    'code' => ErrorCode::NOT_FOUND,
    'error' => 'User not found',
];

// Check error category
$category = ErrorCode::getCategory(ErrorCode::NOT_FOUND); // 'resource'

// Check if client should retry
$shouldRetry = ErrorCode::isRecoverable(ErrorCode::RATE_LIMIT_EXCEEDED); // true

// Map to HTTP status
$httpStatus = ErrorCode::getHttpStatus(ErrorCode::NOT_FOUND); // 404

// Validate error codes
if (ErrorCode::isValid($code)) {
    // Known error code
}
```

## Error Categories

| Category | Codes | Description |
|----------|-------|-------------|
| `access` | INSUFFICIENT_SCOPE, ADMIN_REQUIRED, ACCESS_DENIED, RATE_LIMIT_EXCEEDED | Permission/auth errors |
| `resource` | NOT_FOUND, ALREADY_EXISTS, ENTITY_IN_USE, ENTITY_PROTECTED | Entity/resource state errors |
| `validation` | VALIDATION_ERROR, INVALID_NAME, INVALID_FILE_TYPE, PAYLOAD_TOO_LARGE, MISSING_REQUIRED | Input validation errors |
| `operation` | INTERNAL_ERROR, OPERATION_FAILED, TIMEOUT, CONFIRMATION_REQUIRED | Operation execution errors |
| `domain` | TEMPLATE_NOT_FOUND, CRON_FAILED, MIGRATION_FAILED, etc. | Domain-specific errors |

## All Error Codes

### Access Control
- `INSUFFICIENT_SCOPE` - Write operations not allowed
- `ADMIN_REQUIRED` - Operation requires admin scope
- `ACCESS_DENIED` - Generic access denied
- `RATE_LIMIT_EXCEEDED` - Rate limit exceeded

### Resource
- `NOT_FOUND` - Entity/resource not found
- `ALREADY_EXISTS` - Duplicate entity
- `ENTITY_IN_USE` - Cannot delete/modify entity in use
- `ENTITY_PROTECTED` - Protected entity

### Validation
- `VALIDATION_ERROR` - Input validation failed
- `INVALID_NAME` - Invalid machine name format
- `INVALID_FILE_TYPE` - Invalid file type
- `PAYLOAD_TOO_LARGE` - Size limit exceeded
- `MISSING_REQUIRED` - Required parameter missing

### Operation
- `INTERNAL_ERROR` - Internal server error
- `OPERATION_FAILED` - Operation failed
- `TIMEOUT` - Operation timed out
- `CONFIRMATION_REQUIRED` - Confirmation needed

### Domain-Specific
- `TEMPLATE_NOT_FOUND` - Template not found
- `CRON_FAILED` - Cron job failed
- `MIGRATION_FAILED` - Migration failed
- `RECIPE_FAILED` - Recipe application failed
- `CONFIG_ERROR` - Configuration error
- `MEDIA_ERROR` - Media processing failed
- `SERVICE_UNAVAILABLE` - External service unavailable

## HTTP Status Mapping

| Error Code | HTTP Status |
|------------|-------------|
| ACCESS_DENIED, INSUFFICIENT_SCOPE, ADMIN_REQUIRED | 403 |
| RATE_LIMIT_EXCEEDED | 429 |
| NOT_FOUND, TEMPLATE_NOT_FOUND | 404 |
| ALREADY_EXISTS, ENTITY_IN_USE, ENTITY_PROTECTED | 409 |
| VALIDATION_ERROR, INVALID_NAME, INVALID_FILE_TYPE, MISSING_REQUIRED | 400 |
| PAYLOAD_TOO_LARGE | 413 |
| TIMEOUT | 408 |
| SERVICE_UNAVAILABLE | 503 |
| All others | 500 |

## Recoverable Errors

These errors may resolve on retry:
- `RATE_LIMIT_EXCEEDED` - Wait and retry
- `TIMEOUT` - Retry with backoff
- `SERVICE_UNAVAILABLE` - External service may recover
- `INTERNAL_ERROR` - Transient server issue

## License

MIT
