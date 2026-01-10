<?php

declare(strict_types=1);

namespace CodeWheel\McpErrorCodes\Tests;

use CodeWheel\McpErrorCodes\ErrorCode;
use CodeWheel\McpErrorCodes\McpError;
use PHPUnit\Framework\TestCase;

/**
 * @covers \CodeWheel\McpErrorCodes\McpError
 */
final class McpErrorTest extends TestCase
{
    public function testNotFound(): void
    {
        $error = McpError::notFound('user', 'user-123');

        $this->assertSame(ErrorCode::NOT_FOUND, $error->getCode());
        $this->assertStringContainsString('user', $error->getMessage());
        $this->assertStringContainsString('user-123', $error->getMessage());
        $this->assertSame('user', $error->getDetails()['entity_type']);
        $this->assertSame('user-123', $error->getDetails()['identifier']);
    }

    public function testValidation(): void
    {
        $error = McpError::validation('email', 'Invalid format');

        $this->assertSame(ErrorCode::VALIDATION_ERROR, $error->getCode());
        $this->assertSame('email', $error->getDetails()['field']);
        $this->assertSame('Invalid format', $error->getDetails()['reason']);
    }

    public function testAccessDenied(): void
    {
        $error = McpError::accessDenied('delete', 'Requires admin permission');

        $this->assertSame(ErrorCode::ACCESS_DENIED, $error->getCode());
        $this->assertStringContainsString('delete', $error->getMessage());
        $this->assertStringContainsString('admin permission', $error->getMessage());
    }

    public function testRateLimited(): void
    {
        $error = McpError::rateLimited('tool_calls', 60);

        $this->assertSame(ErrorCode::RATE_LIMIT_EXCEEDED, $error->getCode());
        $this->assertSame(60, $error->getRetryAfter());
        $this->assertSame('tool_calls', $error->getDetails()['limit_type']);
    }

    public function testWithSuggestion(): void
    {
        $error = McpError::notFound('user', '123')
            ->withSuggestion('Check the user ID');

        $this->assertStringContainsString('Check the user ID', $error->getMessage());
        $this->assertSame('Check the user ID', $error->getSuggestion());
    }

    public function testWithContext(): void
    {
        $error = McpError::internal('Something failed')
            ->withContext(['debug' => 'info']);

        $this->assertSame(['debug' => 'info'], $error->getContext());
    }

    public function testWithDetail(): void
    {
        $error = McpError::custom('CUSTOM_CODE', 'Custom error')
            ->withDetail('custom_key', 'custom_value');

        $this->assertSame('custom_value', $error->getDetails()['custom_key']);
    }

    public function testToArray(): void
    {
        $error = McpError::notFound('config', 'my_setting')
            ->withSuggestion('Check spelling');

        $array = $error->toArray();

        $this->assertFalse($array['success']);
        $this->assertSame(ErrorCode::NOT_FOUND, $array['code']);
        $this->assertStringContainsString('my_setting', $array['error']);
        $this->assertArrayHasKey('details', $array);
    }

    public function testToArrayWithRetryAfter(): void
    {
        $error = McpError::rateLimited('api', 30);
        $array = $error->toArray();

        $this->assertSame(30, $array['retry_after']);
    }

    public function testGetCategory(): void
    {
        $this->assertSame('resource', McpError::notFound('x', 'y')->getCategory());
        $this->assertSame('validation', McpError::validation('x', 'y')->getCategory());
        $this->assertSame('access', McpError::accessDenied('x')->getCategory());
    }

    public function testGetHttpStatus(): void
    {
        $this->assertSame(404, McpError::notFound('x', 'y')->getHttpStatus());
        $this->assertSame(400, McpError::validation('x', 'y')->getHttpStatus());
        $this->assertSame(403, McpError::accessDenied('x')->getHttpStatus());
        $this->assertSame(429, McpError::rateLimited('x', 60)->getHttpStatus());
    }

    public function testGetJsonRpcCode(): void
    {
        // Standard codes
        $this->assertSame(-32602, McpError::validation('x', 'y')->getJsonRpcCode());

        // Server-defined codes
        $this->assertSame(-32002, McpError::notFound('x', 'y')->getJsonRpcCode());
        $this->assertSame(-32001, McpError::accessDenied('x')->getJsonRpcCode());
    }

    public function testIsRecoverable(): void
    {
        $this->assertTrue(McpError::rateLimited('x', 60)->isRecoverable());
        $this->assertFalse(McpError::notFound('x', 'y')->isRecoverable());
        $this->assertFalse(McpError::validation('x', 'y')->isRecoverable());
    }

    public function testFromException(): void
    {
        $exception = new \RuntimeException('Database connection failed');
        $error = McpError::fromException($exception, 'Query execution');

        $this->assertSame(ErrorCode::INTERNAL_ERROR, $error->getCode());
        $this->assertStringContainsString('Query execution', $error->getMessage());
        $this->assertStringContainsString('Database connection failed', $error->getMessage());
        $this->assertSame('RuntimeException', $error->getDetails()['exception']);
    }

    public function testAlreadyExists(): void
    {
        $error = McpError::alreadyExists('content_type', 'article');

        $this->assertSame(ErrorCode::ALREADY_EXISTS, $error->getCode());
        $this->assertSame('content_type', $error->getDetails()['entity_type']);
    }

    public function testInsufficientScope(): void
    {
        $error = McpError::insufficientScope('admin', ['read', 'write']);

        $this->assertSame(ErrorCode::INSUFFICIENT_SCOPE, $error->getCode());
        $this->assertSame('admin', $error->getDetails()['required_scope']);
        $this->assertSame(['read', 'write'], $error->getDetails()['current_scopes']);
    }

    public function testReadOnly(): void
    {
        $error = McpError::readOnly('/admin/config');

        $this->assertSame(ErrorCode::READ_ONLY_MODE, $error->getCode());
        $this->assertStringContainsString('/admin/config', $error->getMessage());
    }

    public function testProtectedEntity(): void
    {
        $error = McpError::protectedEntity('role', 'administrator', 'System role');

        $this->assertSame(ErrorCode::ENTITY_PROTECTED, $error->getCode());
        $this->assertSame('System role', $error->getDetails()['reason']);
    }

    public function testEntityInUse(): void
    {
        $error = McpError::entityInUse('vocabulary', 'tags', 500, true);

        $this->assertSame(ErrorCode::ENTITY_IN_USE, $error->getCode());
        $this->assertSame(500, $error->getDetails()['usage_count']);
        $this->assertTrue($error->getDetails()['force_available']);
        $this->assertStringContainsString('force=true', $error->getMessage());
    }

    public function testMissingDependency(): void
    {
        $error = McpError::missingDependency('search_api', 'content indexing');

        $this->assertSame(ErrorCode::MISSING_DEPENDENCY, $error->getCode());
        $this->assertSame('search_api', $error->getDetails()['dependency']);
    }
}
