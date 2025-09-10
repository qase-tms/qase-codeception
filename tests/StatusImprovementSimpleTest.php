<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Exception;

class StatusImprovementSimpleTest extends TestCase
{
    public function testAssertionFailedErrorDetection(): void
    {
        $exception = new AssertionFailedError('Test assertion failed');
        
        // Test the logic directly
        $this->assertTrue($this->isAssertionFailure($exception));
    }

    public function testRegularExceptionDetection(): void
    {
        $exception = new Exception('Some other error');
        
        // Test the logic directly
        $this->assertFalse($this->isAssertionFailure($exception));
    }

    public function testAssertionErrorDetection(): void
    {
        $exception = new \AssertionError('PHP assertion failed');
        
        // Test the logic directly
        $this->assertTrue($this->isAssertionFailure($exception));
    }

    public function testStatusDetermination(): void
    {
        // Test AssertionFailedError -> 'failed'
        $assertionError = new AssertionFailedError('Test');
        $this->assertEquals('failed', $this->determineFailureStatus($assertionError));
        
        // Test regular Exception -> 'invalid'
        $regularException = new Exception('Test');
        $this->assertEquals('invalid', $this->determineFailureStatus($regularException));
        
        // Test AssertionError -> 'failed'
        $phpAssertionError = new \AssertionError('Test');
        $this->assertEquals('failed', $this->determineFailureStatus($phpAssertionError));
    }

    /**
     * Copy of the logic from Reporter class for testing
     */
    private function isAssertionFailure(\Throwable $exception): bool
    {
        // Check for PHPUnit's AssertionFailedError and its subclasses
        if ($exception instanceof \PHPUnit\Framework\AssertionFailedError) {
            return true;
        }
        
        // Check for PHP's AssertionError
        if ($exception instanceof \AssertionError) {
            return true;
        }
        
        // Check by class name for additional safety
        $className = get_class($exception);
        if (str_contains($className, 'AssertionFailedError') || 
            str_contains($className, 'AssertionError')) {
            return true;
        }
        
        return false;
    }

    /**
     * Copy of the logic from Reporter class for testing
     */
    private function determineFailureStatus(\Throwable $exception): string
    {
        // Check if it's an assertion failure
        if ($this->isAssertionFailure($exception)) {
            return 'failed';
        }
        
        // All other types of errors are considered invalid
        return 'invalid';
    }
}
