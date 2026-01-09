<?php

declare(strict_types=1);

namespace CodeWheel\McpErrorCodes;

/**
 * Centralized error codes for MCP server responses.
 *
 * Use these constants instead of hardcoded strings to ensure consistency
 * across MCP servers and enable client-side error handling.
 *
 * Error codes follow a hierarchical naming convention:
 * - Generic codes: UPPERCASE_WITH_UNDERSCORES
 * - Domain-specific codes: DOMAIN_SPECIFIC_ERROR
 *
 * @see https://modelcontextprotocol.io/docs/concepts/error-handling
 */
final class ErrorCode {

  // =========================================================================
  // Access Control Errors
  // =========================================================================

  /**
   * Write operations not allowed (read-only mode or scope restriction).
   */
  public const INSUFFICIENT_SCOPE = 'INSUFFICIENT_SCOPE';

  /**
   * Operation requires admin scope.
   */
  public const ADMIN_REQUIRED = 'ADMIN_REQUIRED';

  /**
   * Generic access denied.
   */
  public const ACCESS_DENIED = 'ACCESS_DENIED';

  /**
   * Rate limit exceeded.
   */
  public const RATE_LIMIT_EXCEEDED = 'RATE_LIMIT_EXCEEDED';

  /**
   * Site/system is in read-only mode.
   */
  public const READ_ONLY_MODE = 'READ_ONLY_MODE';

  // =========================================================================
  // Resource Errors
  // =========================================================================

  /**
   * Requested entity/resource not found.
   */
  public const NOT_FOUND = 'NOT_FOUND';

  /**
   * Entity already exists (duplicate).
   */
  public const ALREADY_EXISTS = 'ALREADY_EXISTS';

  /**
   * Entity is in use and cannot be deleted/modified.
   */
  public const ENTITY_IN_USE = 'ENTITY_IN_USE';

  /**
   * Entity is protected and cannot be modified.
   */
  public const ENTITY_PROTECTED = 'ENTITY_PROTECTED';

  /**
   * Required dependency (module, service) is missing.
   */
  public const MISSING_DEPENDENCY = 'MISSING_DEPENDENCY';

  // =========================================================================
  // Validation Errors
  // =========================================================================

  /**
   * Input validation failed.
   */
  public const VALIDATION_ERROR = 'VALIDATION_ERROR';

  /**
   * Invalid machine name format.
   */
  public const INVALID_NAME = 'INVALID_NAME';

  /**
   * Invalid file type.
   */
  public const INVALID_FILE_TYPE = 'INVALID_FILE_TYPE';

  /**
   * Payload exceeds size limit.
   */
  public const PAYLOAD_TOO_LARGE = 'PAYLOAD_TOO_LARGE';

  /**
   * Required parameter missing.
   */
  public const MISSING_REQUIRED = 'MISSING_REQUIRED';

  // =========================================================================
  // Operation Errors
  // =========================================================================

  /**
   * Internal server error.
   */
  public const INTERNAL_ERROR = 'INTERNAL_ERROR';

  /**
   * Operation failed.
   */
  public const OPERATION_FAILED = 'OPERATION_FAILED';

  /**
   * Operation timed out.
   */
  public const TIMEOUT = 'TIMEOUT';

  /**
   * User confirmation required before destructive operation.
   */
  public const CONFIRMATION_REQUIRED = 'CONFIRMATION_REQUIRED';

  /**
   * Tool not found or invalid.
   */
  public const INVALID_TOOL = 'INVALID_TOOL';

  /**
   * Tool execution failed.
   */
  public const EXECUTION_FAILED = 'EXECUTION_FAILED';

  /**
   * Tool instantiation failed.
   */
  public const INSTANTIATION_FAILED = 'INSTANTIATION_FAILED';

  // =========================================================================
  // Domain-Specific Errors (extend as needed)
  // =========================================================================

  /**
   * Template not found.
   */
  public const TEMPLATE_NOT_FOUND = 'TEMPLATE_NOT_FOUND';

  /**
   * Cron job failed.
   */
  public const CRON_FAILED = 'CRON_FAILED';

  /**
   * Migration failed.
   */
  public const MIGRATION_FAILED = 'MIGRATION_FAILED';

  /**
   * Recipe application failed.
   */
  public const RECIPE_FAILED = 'RECIPE_FAILED';

  /**
   * Configuration import/export failed.
   */
  public const CONFIG_ERROR = 'CONFIG_ERROR';

  /**
   * Media processing failed.
   */
  public const MEDIA_ERROR = 'MEDIA_ERROR';

  /**
   * External service unavailable.
   */
  public const SERVICE_UNAVAILABLE = 'SERVICE_UNAVAILABLE';

  // =========================================================================
  // Helper Methods
  // =========================================================================

  /**
   * Cached list of all error code constants.
   *
   * @var array<string, string>|null
   */
  private static ?array $cachedCodes = null;

  /**
   * Get all defined error codes.
   *
   * @return array<string, string>
   *   Map of constant names to values.
   */
  public static function all(): array {
    if (self::$cachedCodes === null) {
      $reflection = new \ReflectionClass(self::class);
      self::$cachedCodes = $reflection->getConstants();
    }
    return self::$cachedCodes;
  }

  /**
   * Check if a code is a valid error code.
   *
   * @param string $code
   *   The error code to check.
   *
   * @return bool
   *   TRUE if the code is defined.
   */
  public static function isValid(string $code): bool {
    return in_array($code, self::all(), TRUE);
  }

  /**
   * Get error category from code.
   *
   * @param string $code
   *   The error code.
   *
   * @return string
   *   Category: 'access', 'resource', 'validation', 'operation', 'domain'.
   */
  public static function getCategory(string $code): string {
    return match ($code) {
      self::INSUFFICIENT_SCOPE,
      self::ADMIN_REQUIRED,
      self::ACCESS_DENIED,
      self::RATE_LIMIT_EXCEEDED,
      self::READ_ONLY_MODE => 'access',

      self::NOT_FOUND,
      self::ALREADY_EXISTS,
      self::ENTITY_IN_USE,
      self::ENTITY_PROTECTED,
      self::MISSING_DEPENDENCY => 'resource',

      self::VALIDATION_ERROR,
      self::INVALID_NAME,
      self::INVALID_FILE_TYPE,
      self::PAYLOAD_TOO_LARGE,
      self::MISSING_REQUIRED => 'validation',

      self::INTERNAL_ERROR,
      self::OPERATION_FAILED,
      self::TIMEOUT,
      self::CONFIRMATION_REQUIRED,
      self::INVALID_TOOL,
      self::EXECUTION_FAILED,
      self::INSTANTIATION_FAILED => 'operation',

      default => 'domain',
    };
  }

  /**
   * Check if the error is recoverable (client can retry).
   *
   * @param string $code
   *   The error code.
   *
   * @return bool
   *   TRUE if the error might resolve on retry.
   */
  public static function isRecoverable(string $code): bool {
    return in_array($code, [
      self::RATE_LIMIT_EXCEEDED,
      self::TIMEOUT,
      self::SERVICE_UNAVAILABLE,
      self::INTERNAL_ERROR,
    ], TRUE);
  }

  /**
   * Get suggested HTTP status code for an error.
   *
   * @param string $code
   *   The error code.
   *
   * @return int
   *   HTTP status code.
   */
  public static function getHttpStatus(string $code): int {
    return match ($code) {
      self::INSUFFICIENT_SCOPE,
      self::ADMIN_REQUIRED,
      self::ACCESS_DENIED,
      self::READ_ONLY_MODE => 403,

      self::RATE_LIMIT_EXCEEDED => 429,

      self::NOT_FOUND,
      self::TEMPLATE_NOT_FOUND => 404,

      self::ALREADY_EXISTS,
      self::ENTITY_IN_USE,
      self::ENTITY_PROTECTED => 409,

      self::VALIDATION_ERROR,
      self::INVALID_NAME,
      self::INVALID_FILE_TYPE,
      self::MISSING_REQUIRED,
      self::INVALID_TOOL => 400,

      self::PAYLOAD_TOO_LARGE => 413,

      self::TIMEOUT => 408,

      self::SERVICE_UNAVAILABLE => 503,

      default => 500,
    };
  }

}
