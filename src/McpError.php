<?php

declare(strict_types=1);

namespace CodeWheel\McpErrorCodes;

/**
 * Fluent builder for MCP error responses.
 *
 * Example:
 * ```php
 * $error = McpError::notFound('user', 'user-123')
 *     ->withSuggestion('Check if the user ID is correct')
 *     ->withContext(['searched_in' => 'active_users']);
 *
 * return $error->toArray();
 * ```
 */
final class McpError
{
    private string $code;
    private string $message;
    private ?string $suggestion = null;
    private ?int $retryAfter = null;

    /** @var array<string, mixed> */
    private array $context = [];

    /** @var array<string, mixed> */
    private array $details = [];

    private function __construct(string $code, string $message)
    {
        $this->code = $code;
        $this->message = $message;
    }

    // =========================================================================
    // Factory Methods
    // =========================================================================

    /**
     * Creates a "not found" error.
     *
     * @param string $entityType The entity type (e.g., 'user', 'node', 'config').
     * @param string $identifier The identifier that was not found.
     */
    public static function notFound(string $entityType, string $identifier): self
    {
        return (new self(ErrorCode::NOT_FOUND, "The $entityType '$identifier' was not found."))
            ->withDetail('entity_type', $entityType)
            ->withDetail('identifier', $identifier);
    }

    /**
     * Creates an "already exists" error.
     */
    public static function alreadyExists(string $entityType, string $identifier): self
    {
        return (new self(ErrorCode::ALREADY_EXISTS, "A $entityType with ID '$identifier' already exists."))
            ->withDetail('entity_type', $entityType)
            ->withDetail('identifier', $identifier);
    }

    /**
     * Creates a validation error.
     *
     * @param string $field The field that failed validation.
     * @param string $reason Why validation failed.
     */
    public static function validation(string $field, string $reason): self
    {
        return (new self(ErrorCode::VALIDATION_ERROR, "Invalid value for '$field': $reason"))
            ->withDetail('field', $field)
            ->withDetail('reason', $reason);
    }

    /**
     * Creates an access denied error.
     *
     * @param string $operation The operation that was denied.
     * @param string|null $reason Optional reason or required permission.
     */
    public static function accessDenied(string $operation, ?string $reason = null): self
    {
        $message = "Access denied for operation: $operation.";
        if ($reason !== null) {
            $message .= " $reason";
        }

        return (new self(ErrorCode::ACCESS_DENIED, $message))
            ->withDetail('operation', $operation);
    }

    /**
     * Creates an insufficient scope error.
     *
     * @param string $requiredScope The scope that was required.
     * @param string[] $currentScopes The scopes that were present.
     */
    public static function insufficientScope(string $requiredScope, array $currentScopes = []): self
    {
        $scopeList = empty($currentScopes) ? 'none' : implode(', ', $currentScopes);
        $message = "Insufficient scope. Required: '$requiredScope'. Current: $scopeList.";

        return (new self(ErrorCode::INSUFFICIENT_SCOPE, $message))
            ->withDetail('required_scope', $requiredScope)
            ->withDetail('current_scopes', $currentScopes);
    }

    /**
     * Creates a rate limit exceeded error.
     *
     * @param string $limitType The type of limit (e.g., 'tool_calls', 'write_operations').
     * @param int $retryAfterSeconds Seconds until retry is allowed.
     */
    public static function rateLimited(string $limitType, int $retryAfterSeconds = 60): self
    {
        return (new self(ErrorCode::RATE_LIMIT_EXCEEDED, "Rate limit exceeded for $limitType. Try again in $retryAfterSeconds seconds."))
            ->withDetail('limit_type', $limitType)
            ->retryAfter($retryAfterSeconds);
    }

    /**
     * Creates a read-only mode error.
     */
    public static function readOnly(?string $configPath = null): self
    {
        $message = 'Write operations are disabled. The system is in read-only mode.';
        if ($configPath !== null) {
            $message .= " Configure at $configPath.";
        }

        return new self(ErrorCode::READ_ONLY_MODE, $message);
    }

    /**
     * Creates a protected entity error.
     */
    public static function protectedEntity(string $entityType, string $identifier, string $reason): self
    {
        return (new self(ErrorCode::ENTITY_PROTECTED, "The $entityType '$identifier' is protected. $reason"))
            ->withDetail('entity_type', $entityType)
            ->withDetail('identifier', $identifier)
            ->withDetail('reason', $reason);
    }

    /**
     * Creates an "entity in use" error.
     *
     * @param bool $forceAvailable Whether force=true can override.
     */
    public static function entityInUse(string $entityType, string $identifier, int $usageCount, bool $forceAvailable = true): self
    {
        $message = "Cannot delete $entityType '$identifier': used in $usageCount places.";
        if ($forceAvailable) {
            $message .= ' Use force=true to delete anyway.';
        }

        return (new self(ErrorCode::ENTITY_IN_USE, $message))
            ->withDetail('entity_type', $entityType)
            ->withDetail('identifier', $identifier)
            ->withDetail('usage_count', $usageCount)
            ->withDetail('force_available', $forceAvailable);
    }

    /**
     * Creates a missing dependency error.
     */
    public static function missingDependency(string $dependency, string $requiredFor): self
    {
        return (new self(ErrorCode::MISSING_DEPENDENCY, "Missing dependency: '$dependency' is required for $requiredFor."))
            ->withDetail('dependency', $dependency)
            ->withDetail('required_for', $requiredFor);
    }

    /**
     * Creates an internal error.
     */
    public static function internal(string $message): self
    {
        return new self(ErrorCode::INTERNAL_ERROR, $message);
    }

    /**
     * Creates an error from an exception.
     */
    public static function fromException(\Throwable $e, ?string $context = null): self
    {
        $message = $context !== null
            ? "$context: {$e->getMessage()}"
            : $e->getMessage();

        return (new self(ErrorCode::INTERNAL_ERROR, $message))
            ->withDetail('exception', get_class($e));
    }

    /**
     * Creates an error with a custom code.
     */
    public static function custom(string $code, string $message): self
    {
        return new self($code, $message);
    }

    // =========================================================================
    // Builder Methods
    // =========================================================================

    /**
     * Adds a suggestion for how to resolve the error.
     */
    public function withSuggestion(string $suggestion): self
    {
        $this->suggestion = $suggestion;
        return $this;
    }

    /**
     * Adds context data (for debugging/logging, may not be shown to end users).
     *
     * @param array<string, mixed> $context
     */
    public function withContext(array $context): self
    {
        $this->context = array_merge($this->context, $context);
        return $this;
    }

    /**
     * Adds a single detail to the error.
     */
    public function withDetail(string $key, mixed $value): self
    {
        $this->details[$key] = $value;
        return $this;
    }

    /**
     * Sets retry-after hint in seconds.
     */
    public function retryAfter(int $seconds): self
    {
        $this->retryAfter = $seconds;
        return $this;
    }

    // =========================================================================
    // Accessors
    // =========================================================================

    public function getCode(): string
    {
        return $this->code;
    }

    public function getMessage(): string
    {
        $message = $this->message;
        if ($this->suggestion !== null) {
            $message .= ' ' . $this->suggestion;
        }
        return $message;
    }

    public function getRawMessage(): string
    {
        return $this->message;
    }

    public function getSuggestion(): ?string
    {
        return $this->suggestion;
    }

    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }

    /**
     * @return array<string, mixed>
     */
    public function getDetails(): array
    {
        return $this->details;
    }

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    public function getCategory(): string
    {
        return ErrorCode::getCategory($this->code);
    }

    public function getHttpStatus(): int
    {
        return ErrorCode::getHttpStatus($this->code);
    }

    public function isRecoverable(): bool
    {
        return ErrorCode::isRecoverable($this->code);
    }

    public function getJsonRpcCode(): int
    {
        return ErrorCode::getJsonRpcCode($this->code);
    }

    // =========================================================================
    // Conversion Methods
    // =========================================================================

    /**
     * Converts to a standard array format.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [
            'success' => false,
            'error' => $this->getMessage(),
            'code' => $this->code,
        ];

        if (!empty($this->details)) {
            $result['details'] = $this->details;
        }

        if ($this->retryAfter !== null) {
            $result['retry_after'] = $this->retryAfter;
        }

        return $result;
    }

    /**
     * Converts to MCP CallToolResult.
     *
     * Requires mcp/sdk package.
     *
     * @return \Mcp\Schema\Result\CallToolResult
     * @throws \RuntimeException If mcp/sdk is not installed.
     */
    public function toCallToolResult(): object
    {
        if (!class_exists(\Mcp\Schema\Result\CallToolResult::class)) {
            throw new \RuntimeException(
                'mcp/sdk package is required for toCallToolResult(). Install with: composer require mcp/sdk'
            );
        }

        $content = [new \Mcp\Schema\Content\TextContent($this->getMessage())];
        $structured = $this->toArray();

        return new \Mcp\Schema\Result\CallToolResult($content, true, $structured);
    }

    /**
     * Converts to JSON-RPC Error.
     *
     * Requires mcp/sdk package.
     *
     * @return \Mcp\Schema\JsonRpc\Error
     * @throws \RuntimeException If mcp/sdk is not installed.
     */
    public function toJsonRpcError(): object
    {
        if (!class_exists(\Mcp\Schema\JsonRpc\Error::class)) {
            throw new \RuntimeException(
                'mcp/sdk package is required for toJsonRpcError(). Install with: composer require mcp/sdk'
            );
        }

        $data = $this->toArray();
        unset($data['success'], $data['error']);

        return new \Mcp\Schema\JsonRpc\Error(
            null, // JSON-RPC request ID (null for notifications/errors)
            $this->getJsonRpcCode(),
            $this->getMessage(),
            empty($data) ? null : $data
        );
    }
}
