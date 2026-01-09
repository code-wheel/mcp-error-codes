<?php

declare(strict_types=1);

namespace CodeWheel\McpErrorCodes\Tests;

use CodeWheel\McpErrorCodes\ErrorCode;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ErrorCode.
 */
#[\PHPUnit\Framework\Attributes\CoversClass(ErrorCode::class)]
final class ErrorCodeTest extends TestCase {

  public function testAllReturnsAllConstants(): void {
    $all = ErrorCode::all();

    $this->assertIsArray($all);
    $this->assertArrayHasKey('NOT_FOUND', $all);
    $this->assertArrayHasKey('ACCESS_DENIED', $all);
    $this->assertArrayHasKey('VALIDATION_ERROR', $all);
    $this->assertSame('NOT_FOUND', $all['NOT_FOUND']);
  }

  public function testIsValidReturnsTrueForDefinedCodes(): void {
    $this->assertTrue(ErrorCode::isValid('NOT_FOUND'));
    $this->assertTrue(ErrorCode::isValid('ACCESS_DENIED'));
    $this->assertTrue(ErrorCode::isValid('RATE_LIMIT_EXCEEDED'));
  }

  public function testIsValidReturnsFalseForUndefinedCodes(): void {
    $this->assertFalse(ErrorCode::isValid('UNKNOWN_CODE'));
    $this->assertFalse(ErrorCode::isValid(''));
    $this->assertFalse(ErrorCode::isValid('not_found'));
  }

  #[\PHPUnit\Framework\Attributes\DataProvider('categoryProvider')]
  public function testGetCategoryReturnsCorrectCategory(string $code, string $expectedCategory): void {
    $this->assertSame($expectedCategory, ErrorCode::getCategory($code));
  }

  public static function categoryProvider(): array {
    return [
      // Access category
      [ErrorCode::INSUFFICIENT_SCOPE, 'access'],
      [ErrorCode::ADMIN_REQUIRED, 'access'],
      [ErrorCode::ACCESS_DENIED, 'access'],
      [ErrorCode::RATE_LIMIT_EXCEEDED, 'access'],

      // Resource category
      [ErrorCode::NOT_FOUND, 'resource'],
      [ErrorCode::ALREADY_EXISTS, 'resource'],
      [ErrorCode::ENTITY_IN_USE, 'resource'],
      [ErrorCode::ENTITY_PROTECTED, 'resource'],

      // Validation category
      [ErrorCode::VALIDATION_ERROR, 'validation'],
      [ErrorCode::INVALID_NAME, 'validation'],
      [ErrorCode::INVALID_FILE_TYPE, 'validation'],
      [ErrorCode::PAYLOAD_TOO_LARGE, 'validation'],
      [ErrorCode::MISSING_REQUIRED, 'validation'],

      // Operation category
      [ErrorCode::INTERNAL_ERROR, 'operation'],
      [ErrorCode::OPERATION_FAILED, 'operation'],
      [ErrorCode::TIMEOUT, 'operation'],
      [ErrorCode::CONFIRMATION_REQUIRED, 'operation'],

      // Domain category (default)
      [ErrorCode::TEMPLATE_NOT_FOUND, 'domain'],
      [ErrorCode::CRON_FAILED, 'domain'],
      [ErrorCode::MIGRATION_FAILED, 'domain'],
    ];
  }

  public function testGetCategoryReturnsDomainForUnknownCodes(): void {
    $this->assertSame('domain', ErrorCode::getCategory('CUSTOM_ERROR'));
  }

  public function testIsRecoverableReturnsTrueForRecoverableCodes(): void {
    $this->assertTrue(ErrorCode::isRecoverable(ErrorCode::RATE_LIMIT_EXCEEDED));
    $this->assertTrue(ErrorCode::isRecoverable(ErrorCode::TIMEOUT));
    $this->assertTrue(ErrorCode::isRecoverable(ErrorCode::SERVICE_UNAVAILABLE));
    $this->assertTrue(ErrorCode::isRecoverable(ErrorCode::INTERNAL_ERROR));
  }

  public function testIsRecoverableReturnsFalseForNonRecoverableCodes(): void {
    $this->assertFalse(ErrorCode::isRecoverable(ErrorCode::NOT_FOUND));
    $this->assertFalse(ErrorCode::isRecoverable(ErrorCode::ACCESS_DENIED));
    $this->assertFalse(ErrorCode::isRecoverable(ErrorCode::VALIDATION_ERROR));
    $this->assertFalse(ErrorCode::isRecoverable(ErrorCode::ALREADY_EXISTS));
  }

  #[\PHPUnit\Framework\Attributes\DataProvider('httpStatusProvider')]
  public function testGetHttpStatusReturnsCorrectStatus(string $code, int $expectedStatus): void {
    $this->assertSame($expectedStatus, ErrorCode::getHttpStatus($code));
  }

  public static function httpStatusProvider(): array {
    return [
      // 403 Forbidden
      [ErrorCode::INSUFFICIENT_SCOPE, 403],
      [ErrorCode::ADMIN_REQUIRED, 403],
      [ErrorCode::ACCESS_DENIED, 403],

      // 429 Too Many Requests
      [ErrorCode::RATE_LIMIT_EXCEEDED, 429],

      // 404 Not Found
      [ErrorCode::NOT_FOUND, 404],
      [ErrorCode::TEMPLATE_NOT_FOUND, 404],

      // 409 Conflict
      [ErrorCode::ALREADY_EXISTS, 409],
      [ErrorCode::ENTITY_IN_USE, 409],
      [ErrorCode::ENTITY_PROTECTED, 409],

      // 400 Bad Request
      [ErrorCode::VALIDATION_ERROR, 400],
      [ErrorCode::INVALID_NAME, 400],
      [ErrorCode::INVALID_FILE_TYPE, 400],
      [ErrorCode::MISSING_REQUIRED, 400],

      // 413 Payload Too Large
      [ErrorCode::PAYLOAD_TOO_LARGE, 413],

      // 408 Request Timeout
      [ErrorCode::TIMEOUT, 408],

      // 503 Service Unavailable
      [ErrorCode::SERVICE_UNAVAILABLE, 503],

      // 500 Internal Server Error (default)
      [ErrorCode::INTERNAL_ERROR, 500],
      [ErrorCode::OPERATION_FAILED, 500],
    ];
  }

  public function testGetHttpStatusReturns500ForUnknownCodes(): void {
    $this->assertSame(500, ErrorCode::getHttpStatus('UNKNOWN_CODE'));
  }

  public function testConstantsHaveExpectedValues(): void {
    // Ensure constants match their names (convention)
    $this->assertSame('NOT_FOUND', ErrorCode::NOT_FOUND);
    $this->assertSame('ACCESS_DENIED', ErrorCode::ACCESS_DENIED);
    $this->assertSame('RATE_LIMIT_EXCEEDED', ErrorCode::RATE_LIMIT_EXCEEDED);
  }

  public function testAllIsCached(): void {
    $first = ErrorCode::all();
    $second = ErrorCode::all();

    // Same array reference due to caching
    $this->assertSame($first, $second);
  }

}
