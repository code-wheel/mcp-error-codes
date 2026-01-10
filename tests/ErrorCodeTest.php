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

      // Domain category (all domain-specific codes)
      [ErrorCode::TEMPLATE_NOT_FOUND, 'domain'],
      [ErrorCode::CRON_FAILED, 'domain'],
      [ErrorCode::MIGRATION_FAILED, 'domain'],
      [ErrorCode::RECIPE_FAILED, 'domain'],
      [ErrorCode::CONFIG_ERROR, 'domain'],
      [ErrorCode::MEDIA_ERROR, 'domain'],
      [ErrorCode::SERVICE_UNAVAILABLE, 'domain'],
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

      // 500 Internal Server Error (default for operation/domain codes)
      [ErrorCode::INTERNAL_ERROR, 500],
      [ErrorCode::OPERATION_FAILED, 500],
      [ErrorCode::CONFIRMATION_REQUIRED, 500],
      [ErrorCode::CRON_FAILED, 500],
      [ErrorCode::MIGRATION_FAILED, 500],
      [ErrorCode::RECIPE_FAILED, 500],
      [ErrorCode::CONFIG_ERROR, 500],
      [ErrorCode::MEDIA_ERROR, 500],
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

  #[\PHPUnit\Framework\Attributes\DataProvider('jsonRpcCodeProvider')]
  public function testGetJsonRpcCodeReturnsCorrectCode(string $code, int $expectedCode): void {
    $this->assertSame($expectedCode, ErrorCode::getJsonRpcCode($code));
  }

  public static function jsonRpcCodeProvider(): array {
    return [
      // -32602 Invalid params
      [ErrorCode::VALIDATION_ERROR, -32602],
      [ErrorCode::INVALID_NAME, -32602],
      [ErrorCode::INVALID_FILE_TYPE, -32602],
      [ErrorCode::MISSING_REQUIRED, -32602],

      // -32601 Method not found
      [ErrorCode::INVALID_TOOL, -32601],

      // -32603 Internal error
      [ErrorCode::INTERNAL_ERROR, -32603],
      [ErrorCode::EXECUTION_FAILED, -32603],
      [ErrorCode::INSTANTIATION_FAILED, -32603],

      // -32001 Access denied
      [ErrorCode::ACCESS_DENIED, -32001],
      [ErrorCode::INSUFFICIENT_SCOPE, -32001],
      [ErrorCode::ADMIN_REQUIRED, -32001],

      // -32002 Not found
      [ErrorCode::NOT_FOUND, -32002],
      [ErrorCode::TEMPLATE_NOT_FOUND, -32002],

      // -32003 Rate limited
      [ErrorCode::RATE_LIMIT_EXCEEDED, -32003],

      // -32004 Read-only
      [ErrorCode::READ_ONLY_MODE, -32004],

      // -32005 Conflict
      [ErrorCode::ALREADY_EXISTS, -32005],
      [ErrorCode::ENTITY_IN_USE, -32005],
      [ErrorCode::ENTITY_PROTECTED, -32005],

      // -32006 Missing dependency
      [ErrorCode::MISSING_DEPENDENCY, -32006],

      // -32007 Timeout
      [ErrorCode::TIMEOUT, -32007],

      // -32008 Service unavailable
      [ErrorCode::SERVICE_UNAVAILABLE, -32008],

      // -32009 Payload too large
      [ErrorCode::PAYLOAD_TOO_LARGE, -32009],

      // -32010 Confirmation required
      [ErrorCode::CONFIRMATION_REQUIRED, -32010],

      // -32011 Operation failed
      [ErrorCode::OPERATION_FAILED, -32011],
      [ErrorCode::CRON_FAILED, -32011],
      [ErrorCode::MIGRATION_FAILED, -32011],
      [ErrorCode::RECIPE_FAILED, -32011],
      [ErrorCode::CONFIG_ERROR, -32011],
      [ErrorCode::MEDIA_ERROR, -32011],
    ];
  }

  public function testGetJsonRpcCodeReturnsDefaultForUnknownCodes(): void {
    $this->assertSame(-32000, ErrorCode::getJsonRpcCode('UNKNOWN_CODE'));
  }

  public function testGetCategoryForReadOnlyMode(): void {
    $this->assertSame('access', ErrorCode::getCategory(ErrorCode::READ_ONLY_MODE));
  }

  public function testGetCategoryForMissingDependency(): void {
    $this->assertSame('resource', ErrorCode::getCategory(ErrorCode::MISSING_DEPENDENCY));
  }

  public function testGetCategoryForToolErrors(): void {
    $this->assertSame('operation', ErrorCode::getCategory(ErrorCode::INVALID_TOOL));
    $this->assertSame('operation', ErrorCode::getCategory(ErrorCode::EXECUTION_FAILED));
    $this->assertSame('operation', ErrorCode::getCategory(ErrorCode::INSTANTIATION_FAILED));
  }

  public function testGetHttpStatusForReadOnlyMode(): void {
    $this->assertSame(403, ErrorCode::getHttpStatus(ErrorCode::READ_ONLY_MODE));
  }

  public function testGetHttpStatusForMissingDependency(): void {
    $this->assertSame(500, ErrorCode::getHttpStatus(ErrorCode::MISSING_DEPENDENCY));
  }

  public function testGetHttpStatusForInvalidTool(): void {
    $this->assertSame(400, ErrorCode::getHttpStatus(ErrorCode::INVALID_TOOL));
  }

  public function testGetHttpStatusForExecutionFailed(): void {
    $this->assertSame(500, ErrorCode::getHttpStatus(ErrorCode::EXECUTION_FAILED));
  }

  public function testGetHttpStatusForInstantiationFailed(): void {
    $this->assertSame(500, ErrorCode::getHttpStatus(ErrorCode::INSTANTIATION_FAILED));
  }

}
