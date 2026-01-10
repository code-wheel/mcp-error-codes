<?php

declare(strict_types=1);

namespace CodeWheel\McpErrorCodes;

/**
 * Collection of McpError instances.
 *
 * Useful for collecting multiple validation errors before returning a response.
 *
 * Example:
 * ```php
 * $errors = new ErrorBag();
 *
 * if (empty($input['email'])) {
 *     $errors->add(McpError::validation('email', 'Required'));
 * }
 * if (empty($input['name'])) {
 *     $errors->add(McpError::validation('name', 'Required'));
 * }
 *
 * if ($errors->hasErrors()) {
 *     return $errors->toCallToolResult();
 * }
 * ```
 *
 * @implements \IteratorAggregate<int, McpError>
 */
final class ErrorBag implements \Countable, \IteratorAggregate
{
    /** @var McpError[] */
    private array $errors = [];

    /**
     * Creates an empty error bag.
     */
    public function __construct()
    {
    }

    /**
     * Creates an error bag from an array of errors.
     *
     * @param McpError[] $errors
     */
    public static function fromArray(array $errors): self
    {
        $bag = new self();
        foreach ($errors as $error) {
            $bag->add($error);
        }
        return $bag;
    }

    /**
     * Adds an error to the bag.
     */
    public function add(McpError $error): self
    {
        $this->errors[] = $error;
        return $this;
    }

    /**
     * Adds a validation error to the bag (convenience method).
     */
    public function addValidation(string $field, string $reason): self
    {
        return $this->add(McpError::validation($field, $reason));
    }

    /**
     * Merges another error bag into this one.
     */
    public function merge(ErrorBag $other): self
    {
        foreach ($other->errors as $error) {
            $this->errors[] = $error;
        }
        return $this;
    }

    /**
     * Checks if the bag has any errors.
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Checks if the bag is empty.
     */
    public function isEmpty(): bool
    {
        return empty($this->errors);
    }

    /**
     * Returns the number of errors.
     */
    public function count(): int
    {
        return count($this->errors);
    }

    /**
     * Returns all errors.
     *
     * @return McpError[]
     */
    public function all(): array
    {
        return $this->errors;
    }

    /**
     * Returns the first error, or null if empty.
     */
    public function first(): ?McpError
    {
        return $this->errors[0] ?? null;
    }

    /**
     * Returns errors for a specific field (validation errors only).
     *
     * @return McpError[]
     */
    public function forField(string $field): array
    {
        return array_filter(
            $this->errors,
            static fn(McpError $e): bool => ($e->getDetails()['field'] ?? null) === $field
        );
    }

    /**
     * Returns errors by category.
     *
     * @return McpError[]
     */
    public function byCategory(string $category): array
    {
        return array_filter(
            $this->errors,
            static fn(McpError $e): bool => $e->getCategory() === $category
        );
    }

    /**
     * Clears all errors.
     */
    public function clear(): self
    {
        $this->errors = [];
        return $this;
    }

    /**
     * @return \ArrayIterator<int, McpError>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->errors);
    }

    // =========================================================================
    // Conversion Methods
    // =========================================================================

    /**
     * Converts to array format.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        if (empty($this->errors)) {
            return [
                'success' => true,
                'errors' => [],
            ];
        }

        // Use first error as primary
        $primary = $this->errors[0];
        $result = [
            'success' => false,
            'error' => $this->getSummaryMessage(),
            'code' => $primary->getCode(),
            'error_count' => count($this->errors),
        ];

        // Collect all error details
        $details = [];
        foreach ($this->errors as $error) {
            $details[] = [
                'message' => $error->getRawMessage(),
                'code' => $error->getCode(),
                'details' => $error->getDetails(),
            ];
        }
        $result['errors'] = $details;

        return $result;
    }

    /**
     * Gets a summary message for all errors.
     */
    public function getSummaryMessage(): string
    {
        $count = count($this->errors);

        if ($count === 0) {
            return 'No errors';
        }

        if ($count === 1) {
            return $this->errors[0]->getMessage();
        }

        // Group by category for smarter summary
        $categories = [];
        foreach ($this->errors as $error) {
            $cat = $error->getCategory();
            $categories[$cat] = ($categories[$cat] ?? 0) + 1;
        }

        if (count($categories) === 1 && isset($categories['validation'])) {
            return "$count validation errors occurred.";
        }

        return "$count errors occurred.";
    }

    /**
     * Converts to MCP CallToolResult.
     *
     * @return \Mcp\Schema\Result\CallToolResult
     * @throws \RuntimeException If mcp/sdk is not installed.
     */
    public function toCallToolResult(): object
    {
        // @codeCoverageIgnoreStart
        if (!class_exists(\Mcp\Schema\Result\CallToolResult::class)) {
            throw new \RuntimeException(
                'mcp/sdk package is required for toCallToolResult(). Install with: composer require mcp/sdk'
            );
        }
        // @codeCoverageIgnoreEnd

        if (empty($this->errors)) {
            return new \Mcp\Schema\Result\CallToolResult(
                [new \Mcp\Schema\Content\TextContent('No errors')],
                false,
                ['success' => true]
            );
        }

        $content = [new \Mcp\Schema\Content\TextContent($this->getSummaryMessage())];
        $structured = $this->toArray();

        return new \Mcp\Schema\Result\CallToolResult($content, true, $structured);
    }

    /**
     * Converts to JSON-RPC Error (uses first error).
     *
     * @return \Mcp\Schema\JsonRpc\Error
     * @throws \RuntimeException If mcp/sdk is not installed or bag is empty.
     */
    public function toJsonRpcError(): object
    {
        // @codeCoverageIgnoreStart
        if (!class_exists(\Mcp\Schema\JsonRpc\Error::class)) {
            throw new \RuntimeException(
                'mcp/sdk package is required for toJsonRpcError(). Install with: composer require mcp/sdk'
            );
        }
        // @codeCoverageIgnoreEnd

        if (empty($this->errors)) {
            throw new \RuntimeException('Cannot convert empty ErrorBag to JsonRpcError');
        }

        $primary = $this->errors[0];
        $data = $this->toArray();
        unset($data['success'], $data['error']);

        return new \Mcp\Schema\JsonRpc\Error(
            0, // JSON-RPC request ID (0 as placeholder when ID unknown)
            $primary->getJsonRpcCode(),
            $this->getSummaryMessage(),
            $data
        );
    }
}
