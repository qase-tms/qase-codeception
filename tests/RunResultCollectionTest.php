<?php

namespace Tests;

use Codeception\Event\FailEvent;
use Codeception\Event\TestEvent;
use Codeception\Test\Cest;
use Codeception\Test\Test;
use Codeception\Test\Unit;
use PHPUnit\Framework\TestCase;
use Qase\Codeception\RunResultCollection;
use Qase\PhpClientUtils\ConsoleLogger;
use Qase\PhpClientUtils\LoggerInterface;
use Qase\PhpClientUtils\RunResult;

class RunResultCollectionTest extends TestCase
{

    protected function setUp(): void
    {
        // Codeception 4 dependencies fix. Original comment: Compatibility with Symfony 5.
        if (
            !class_exists('Symfony\Component\EventDispatcher\Event')
            && class_exists('Symfony\Contracts\EventDispatcher\Event')
        ) {
            class_alias('Symfony\Contracts\EventDispatcher\Event', 'Symfony\Component\EventDispatcher\Event');
        }
    }

    /**
     * @dataProvider autoCreateDefectDataProvider
     */
    public function testAutoCreateDefect(string $title, string $status, float $time, bool $expected)
    {
        $runResult = $this->getMockBuilder(RunResult::class)
            ->setConstructorArgs(['PRJ', null, true])
            ->getMock();
        $runResult->expects($this->once())
            ->method('addResult')
            ->with(
                $this->callback(function ($result) use ($expected) {
                    return isset($result['defect']) && $result['defect'] === $expected;
                })
            );

        $runResultCollection = $this->createRunResultCollection($runResult);
        $runResultCollection->add($status, $this->createTestEvent());
    }

    public function autoCreateDefectDataProvider(): array
    {
        return [
            ['Test (Qase ID: 1)', 'failed', 1, true],
            ['Test (Qase ID: 2)', 'passed', 1, false],
            ['Test (Qase ID: 3)', 'skipped', 1, false],
            ['Test (Qase ID: 4)', 'disabled', 1, false],
            ['Test (Qase ID: 5)', 'pending', 1, false],
        ];
    }

    public function testGettingRunResultFromCollection()
    {
        $runResultCollection = $this->createRunResultCollection();
        $this->assertInstanceOf(RunResult::class, $runResultCollection->get());
    }

    public function testResultCollectionIsEmptyWhenReportingIsDisabled()
    {
        $isReportingEnabled = false;
        $runResultCollection = $this->createRunResultCollection(null, $isReportingEnabled);
        $runResultCollection->add('failed', $this->createTestEvent());

        $runResult = $runResultCollection->get();

        $this->assertEmpty($runResult->getResults());
    }

    public function testAddingResults()
    {
        // Arrange
        $stackTraceMessage = 'Stack trace text';
        $expectedResult = [
            [
                'status' => 'failed',
                'time' => 1.0,
                'full_test_name' => 'Unit::methodName',
                'stacktrace' => $stackTraceMessage,
                'defect' => true,
            ],
            [
                'status' => 'passed',
                'time' => 0.375,
                'full_test_name' => 'Unit::methodName',
                'stacktrace' => null,
                'defect' => false,
            ],
        ];

        // Act: Initialize empty Collection
        $runResultCollection = $this->createRunResultCollection();
        // Assert: Insure results are empty
        $runResultWithoutResults = $runResultCollection->get();
        $this->assertEmpty($runResultWithoutResults->getResults());

        // Act: Add run results to the collection
        $runResultCollection->add('failed', $this->createFailEvent(null, 1.0, $stackTraceMessage));
        $runResultCollection->add('passed', $this->createTestEvent(null, 0.375));
        // Assert: Check collection results
        $runResultWithResults = $runResultCollection->get();
        $this->assertSame($runResultWithResults->getResults(), $expectedResult);
    }

    private function createRunResultCollection(
        ?RunResult       $runResult = null,
        bool             $isReportingEnabled = true,
        ?LoggerInterface $logger = null
    ): RunResultCollection
    {
        $runResult = $runResult ?: $this->createRunResult();
        $logger = $logger ?: $this->createLogger();

        return new RunResultCollection($runResult, $isReportingEnabled, $logger);
    }

    private function createRunResult(): RunResult
    {
        return new RunResult('PRJ', 1, true, null);
    }

    private function createLogger(): ConsoleLogger
    {
        return $this->getMockBuilder(ConsoleLogger::class)->getMock();
    }

    private function createTestEvent(?string $className = null, float $time = 1.0): TestEvent
    {
        $test = $this->createTest($className);
        $event = $this->getMockBuilder(TestEvent::class)
            ->setConstructorArgs([$test, $time])->getMock();
        $event->method('getTest')->willReturn($test);
        $event->method('getTime')->willReturn($time);

        return $event;
    }

    private function createFailEvent(?string $className = null, float $time = 1.0, $stackTraceMessage = ''): FailEvent
    {
        $test = $this->createTest($className);
        $exception = new \Exception($stackTraceMessage);
        $event = $this->getMockBuilder(FailEvent::class)
            ->setConstructorArgs([$test, $time, $exception])->getMock();
        $event->method('getTest')->willReturn($test);
        $event->method('getTime')->willReturn($time);
        $event->method('getFail')->willReturn($exception);

        return $event;
    }

    /**
     * @throws \ReflectionException
     */
    private function createTest(?string $className = null)
    {
        $className = $className ?: Unit::class;
        $reflectionClass = new \ReflectionClass($className);
        $test = $this->getMockBuilder($className)->setMockClassName($reflectionClass->getShortName())->getMock();
        $test->method('getName')->willReturn('methodName');

        return $test;
    }

}
