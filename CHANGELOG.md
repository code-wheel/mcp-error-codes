# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.3] - 2026-01-09

### Fixed
- Remove unreachable code branch in `toJsonRpcError()` for 100% test coverage

## [1.2.0] - 2025-01-09

### Added
- `McpError` fluent builder class with 15+ factory methods
  - `notFound()`, `accessDenied()`, `validation()`, `rateLimited()`, etc.
  - `withSuggestion()`, `withContext()`, `withDetail()` for adding metadata
  - `toArray()`, `toToolResult()`, `toJsonRpcError()` conversion methods
  - `retryAfter()` for rate limiting hints
- `ErrorBag` class for collecting multiple errors
  - `add()`, `addValidation()`, `merge()` for building error collections
  - `forField()`, `byCategory()` for filtering errors
  - `toToolResult()` for MCP responses
  - Implements `Countable` and `IteratorAggregate`
- `ErrorCode::getJsonRpcCode()` for JSON-RPC 2.0 error code mapping

### Changed
- Updated composer.json description to reflect new features

## [1.1.0] - 2025-01-08

### Added
- Additional domain-specific error codes
- `isRecoverable()` helper method
- `getHttpStatus()` for HTTP status code mapping

## [1.0.0] - 2025-01-07

### Added
- Initial release with `ErrorCode` constants
- Error categorization (access, resource, validation, operation, domain)
- Helper methods: `getCategory()`, `isValid()`, `getAllCodes()`
