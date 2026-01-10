# MCP Error Codes

[![CI](https://github.com/code-wheel/mcp-error-codes/actions/workflows/ci.yml/badge.svg)](https://github.com/code-wheel/mcp-error-codes/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/code-wheel/mcp-error-codes/graph/badge.svg)](https://codecov.io/gh/code-wheel/mcp-error-codes)
[![Latest Stable Version](https://poser.pugx.org/code-wheel/mcp-error-codes/v)](https://packagist.org/packages/code-wheel/mcp-error-codes)
[![PHP Version](https://img.shields.io/packagist/php-v/code-wheel/mcp-error-codes.svg)](https://packagist.org/packages/code-wheel/mcp-error-codes)
[![License](https://poser.pugx.org/code-wheel/mcp-error-codes/license)](https://packagist.org/packages/code-wheel/mcp-error-codes)

Standardized error codes and fluent error builders for MCP (Model Context Protocol) servers in PHP.

**Zero dependencies** - pure PHP 8.1+.

## Installation

```bash
composer require code-wheel/mcp-error-codes
```

## Quick Start

### Fluent Error Builder

```php
use CodeWheel\McpErrorCodes\McpError;

// Simple errors with factory methods
$error = McpError::notFound('user', 'user-123');
$error = McpError::accessDenied('delete', 'admin permission required');
$error = McpError::validation('email', 'Invalid format');
$error = McpError::rateLimited('tool_calls');

// Add context and suggestions
$error = McpError::notFound('user', 'user-123')
    ->withSuggestion('Check if user ID is correct')
    ->withContext(['searched_in' => 'active_users']);

// Convert to different formats
$array = $error->toArray();           // ['success' => false, 'error' => '...', 'code' => '...']
$result = $error->toToolResult();     // ToolResult with success=false
$rpcError = $error->toJsonRpcError(); // JSON-RPC 2.0 Error object

// Rate limiting with retry hint
$error = McpError::rateLimited('api_calls')
    ->retryAfter(60);
```

### ErrorBag for Multiple Errors

```php
use CodeWheel\McpErrorCodes\ErrorBag;
use CodeWheel\McpErrorCodes\McpError;

$errors = new ErrorBag();

// Collect validation errors
if (empty($input['email'])) {
    $errors->addValidation('email', 'Email is required');
}
if (!filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
    $errors->addValidation('email', 'Invalid email format');
}
if (strlen($input['name'] ?? '') < 2) {
    $errors->addValidation('name', 'Name must be at least 2 characters');
}

// Check and return
if ($errors->hasErrors()) {
    return $errors->toToolResult();
}

// Filter errors by field
$emailErrors = $errors->forField('email');

// Merge error bags
$errors->merge($otherErrors);

// Iterate over errors
foreach ($errors as $error) {
    echo $error->getMessage();
}
```

### Error Code Constants

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

// Map to JSON-RPC 2.0 error codes
$rpcCode = ErrorCode::getJsonRpcCode(ErrorCode::VALIDATION_ERROR); // -32602
$rpcCode = ErrorCode::getJsonRpcCode(ErrorCode::NOT_FOUND);        // -32002
$rpcCode = ErrorCode::getJsonRpcCode(ErrorCode::INTERNAL_ERROR);   // -32603
```

## Available Factory Methods

| Method | Description | Error Code |
|--------|-------------|------------|
| `McpError::notFound($type, $id)` | Entity not found | NOT_FOUND |
| `McpError::accessDenied($action, $reason)` | Permission denied | ACCESS_DENIED |
| `McpError::validation($field, $message)` | Input validation error | VALIDATION_ERROR |
| `McpError::rateLimited($resource)` | Rate limit exceeded | RATE_LIMIT_EXCEEDED |
| `McpError::alreadyExists($type, $id)` | Duplicate entity | ALREADY_EXISTS |
| `McpError::insufficientScope($required)` | Missing scope | INSUFFICIENT_SCOPE |
| `McpError::internalError($message)` | Server error | INTERNAL_ERROR |
| `McpError::timeout($operation)` | Operation timed out | TIMEOUT |
| `McpError::invalidInput($field, $message)` | Invalid input | VALIDATION_ERROR |
| `McpError::missingRequired($field)` | Required field missing | MISSING_REQUIRED |
| `McpError::operationFailed($message)` | Operation failed | OPERATION_FAILED |
| `McpError::serviceUnavailable($service)` | External service down | SERVICE_UNAVAILABLE |
| `McpError::entityProtected($type, $id)` | Cannot modify protected | ENTITY_PROTECTED |
| `McpError::entityInUse($type, $id)` | Cannot delete in-use | ENTITY_IN_USE |
| `McpError::confirmationRequired($action)` | Needs confirmation | CONFIRMATION_REQUIRED |

## JSON-RPC 2.0 Error Code Mapping

MCP uses JSON-RPC 2.0. This package maps semantic error codes to standard JSON-RPC codes:

| Error Code | JSON-RPC Code | Description |
|------------|---------------|-------------|
| VALIDATION_ERROR | -32602 | Invalid params |
| INVALID_TOOL | -32601 | Method not found |
| NOT_FOUND | -32002 | Resource not found |
| ACCESS_DENIED | -32003 | Access denied |
| RATE_LIMIT_EXCEEDED | -32004 | Rate limited |
| INTERNAL_ERROR | -32603 | Internal error |
| TIMEOUT | -32001 | Timeout |

## Error Categories

| Category | Codes | Description |
|----------|-------|-------------|
| `access` | INSUFFICIENT_SCOPE, ADMIN_REQUIRED, ACCESS_DENIED, RATE_LIMIT_EXCEEDED | Permission/auth errors |
| `resource` | NOT_FOUND, ALREADY_EXISTS, ENTITY_IN_USE, ENTITY_PROTECTED | Entity/resource state errors |
| `validation` | VALIDATION_ERROR, INVALID_NAME, INVALID_FILE_TYPE, PAYLOAD_TOO_LARGE, MISSING_REQUIRED | Input validation errors |
| `operation` | INTERNAL_ERROR, OPERATION_FAILED, TIMEOUT, CONFIRMATION_REQUIRED | Operation execution errors |
| `domain` | TEMPLATE_NOT_FOUND, CRON_FAILED, MIGRATION_FAILED, etc. | Domain-specific errors |

## Recoverable Errors

These errors may resolve on retry:
- `RATE_LIMIT_EXCEEDED` - Wait and retry
- `TIMEOUT` - Retry with backoff
- `SERVICE_UNAVAILABLE` - External service may recover
- `INTERNAL_ERROR` - Transient server issue

## License

MIT






