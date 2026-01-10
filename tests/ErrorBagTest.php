<?php

declare(strict_types=1);

namespace CodeWheel\McpErrorCodes\Tests;

use CodeWheel\McpErrorCodes\ErrorBag;
use CodeWheel\McpErrorCodes\McpError;
use PHPUnit\Framework\TestCase;

/**
 * @covers \CodeWheel\McpErrorCodes\ErrorBag
 */
final class ErrorBagTest extends TestCase
{
    public function testEmptyBag(): void
    {
        $bag = new ErrorBag();

        $this->assertTrue($bag->isEmpty());
        $this->assertFalse($bag->hasErrors());
        $this->assertCount(0, $bag);
        $this->assertNull($bag->first());
    }

    public function testAddError(): void
    {
        $bag = new ErrorBag();
        $bag->add(McpError::validation('email', 'Required'));

        $this->assertFalse($bag->isEmpty());
        $this->assertTrue($bag->hasErrors());
        $this->assertCount(1, $bag);
    }

    public function testAddValidation(): void
    {
        $bag = new ErrorBag();
        $bag->addValidation('name', 'Too short');

        $this->assertCount(1, $bag);
        $first = $bag->first();
        $this->assertNotNull($first);
        $this->assertSame('name', $first->getDetails()['field']);
    }

    public function testFromArray(): void
    {
        $errors = [
            McpError::validation('email', 'Invalid'),
            McpError::validation('name', 'Required'),
        ];

        $bag = ErrorBag::fromArray($errors);

        $this->assertCount(2, $bag);
    }

    public function testMerge(): void
    {
        $bag1 = new ErrorBag();
        $bag1->add(McpError::validation('email', 'Required'));

        $bag2 = new ErrorBag();
        $bag2->add(McpError::validation('name', 'Required'));

        $bag1->merge($bag2);

        $this->assertCount(2, $bag1);
    }

    public function testFirst(): void
    {
        $bag = new ErrorBag();
        $bag->add(McpError::validation('email', 'First error'));
        $bag->add(McpError::validation('name', 'Second error'));

        $first = $bag->first();
        $this->assertNotNull($first);
        $this->assertSame('email', $first->getDetails()['field']);
    }

    public function testForField(): void
    {
        $bag = new ErrorBag();
        $bag->add(McpError::validation('email', 'Required'));
        $bag->add(McpError::validation('email', 'Invalid format'));
        $bag->add(McpError::validation('name', 'Required'));

        $emailErrors = $bag->forField('email');
        $this->assertCount(2, $emailErrors);

        $nameErrors = $bag->forField('name');
        $this->assertCount(1, $nameErrors);
    }

    public function testByCategory(): void
    {
        $bag = new ErrorBag();
        $bag->add(McpError::validation('email', 'Required'));
        $bag->add(McpError::notFound('user', '123'));
        $bag->add(McpError::validation('name', 'Required'));

        $validationErrors = $bag->byCategory('validation');
        $this->assertCount(2, $validationErrors);

        $resourceErrors = $bag->byCategory('resource');
        $this->assertCount(1, $resourceErrors);
    }

    public function testClear(): void
    {
        $bag = new ErrorBag();
        $bag->add(McpError::validation('email', 'Required'));

        $this->assertCount(1, $bag);

        $bag->clear();

        $this->assertCount(0, $bag);
        $this->assertTrue($bag->isEmpty());
    }

    public function testIterator(): void
    {
        $bag = new ErrorBag();
        $bag->add(McpError::validation('email', 'Required'));
        $bag->add(McpError::validation('name', 'Required'));

        $count = 0;
        foreach ($bag as $error) {
            $count++;
            $this->assertInstanceOf(McpError::class, $error);
        }

        $this->assertSame(2, $count);
    }

    public function testToArrayEmpty(): void
    {
        $bag = new ErrorBag();
        $array = $bag->toArray();

        $this->assertTrue($array['success']);
        $this->assertEmpty($array['errors']);
    }

    public function testToArrayWithErrors(): void
    {
        $bag = new ErrorBag();
        $bag->add(McpError::validation('email', 'Required'));
        $bag->add(McpError::validation('name', 'Too short'));

        $array = $bag->toArray();

        $this->assertFalse($array['success']);
        $this->assertSame(2, $array['error_count']);
        $this->assertCount(2, $array['errors']);
        $this->assertArrayHasKey('message', $array['errors'][0]);
        $this->assertArrayHasKey('code', $array['errors'][0]);
    }

    public function testGetSummaryMessageSingle(): void
    {
        $bag = new ErrorBag();
        $bag->add(McpError::validation('email', 'Required'));

        $this->assertStringContainsString('email', $bag->getSummaryMessage());
    }

    public function testGetSummaryMessageMultiple(): void
    {
        $bag = new ErrorBag();
        $bag->add(McpError::validation('email', 'Required'));
        $bag->add(McpError::validation('name', 'Required'));
        $bag->add(McpError::validation('age', 'Invalid'));

        $this->assertStringContainsString('3 validation errors', $bag->getSummaryMessage());
    }

    public function testGetSummaryMessageMixed(): void
    {
        $bag = new ErrorBag();
        $bag->add(McpError::validation('email', 'Required'));
        $bag->add(McpError::notFound('user', '123'));

        $this->assertStringContainsString('2 errors', $bag->getSummaryMessage());
    }

    public function testAll(): void
    {
        $bag = new ErrorBag();
        $error1 = McpError::validation('email', 'Required');
        $error2 = McpError::validation('name', 'Required');

        $bag->add($error1);
        $bag->add($error2);

        $all = $bag->all();
        $this->assertCount(2, $all);
        $this->assertSame($error1, $all[0]);
        $this->assertSame($error2, $all[1]);
    }
}
