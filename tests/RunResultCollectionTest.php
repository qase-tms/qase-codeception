<?php

namespace Tests;

use Codeception\Event\FailEvent;
use Codeception\Event\TestEvent;
use Codeception\Test\Cest;
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

        $event = $this->createTestEvent();

        $runResultCollection = $this->createRunResultCollection($runResult);
        $runResultCollection->add($status, $event);
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

    public function testGetReturnsRunResultObject()
    {
        $runResultCollection = $this->createRunResultCollection();
        $this->assertInstanceOf(RunResult::class, $runResultCollection->get());
    }

    public function testAddDoesNothingWhenReportingIsDisabled()
    {
        $runResultCollection = $this->createRunResultCollection(null, false);
        $event = $this->createTestEvent();
        $runResultCollection->add('failed', $event);

        $runResultWithoutResults = $runResultCollection->get();

        $this->assertEmpty($runResultWithoutResults->getResults());
    }

    public function testAddCorrectlyAddsResult()
    {
        $runResultCollection = $this->createRunResultCollection();
        $runResultWithoutResults = $runResultCollection->get();
        $this->assertEmpty($runResultWithoutResults->getResults());

        $stackTraceMessage = 'Stack trace text';

        $testUnit = $this->createTest();
        $exception = new \Exception($stackTraceMessage);
        $eventUnit = $this->getMockBuilder(FailEvent::class)
            ->setConstructorArgs([$testUnit, 1.0, $exception])->getMock();
        $eventUnit->method('getTest')->willReturn($testUnit);
        $eventUnit->method('getFail')->willReturn($exception);
        $eventUnit->method('getTime')->willReturn(1.0);

        $testUnit2 = $this->createTest();
        $eventUnit2 = $this->getMockBuilder(TestEvent::class)
            ->setConstructorArgs([$testUnit2])->getMock();
        $eventUnit2->method('getTest')->willReturn($testUnit2);
        $eventUnit2->method('getTime')->willReturn(0.375);

        $runResultCollection->add('failed', $eventUnit);
        $runResultCollection->add('passed', $eventUnit2);

        $runResultWithResults = $runResultCollection->get();

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

        $this->assertSame($runResultWithResults->getResults(), $expectedResult);
    }

    public function testAddUnsupportedTestTypeCallsLoggerWriteln()
    {
        $logger = $this->createLogger();
        $logger->expects($this->once())
            ->method('writeln')
            ->with($this->equalTo('The test type is not supported yet: UnsupportedTest. Skipped.'));

        $exception = new \Exception('message');
        $test = $this->getMockBuilder(\stdClass::class)->setMockClassName('UnsupportedTest')->getMock();
        $event = $this->getMockBuilder(TestEvent::class)
            ->setConstructorArgs([$test, 1.0, $exception])->getMock();
        $event->method('getTest')->willReturn($test);
        $event->method('getFail')->willReturn($exception);

        $runResultCollection = $this->createRunResultCollection(null, true, $logger);
        $runResultCollection->add('passed', $event);
    }

    private function createLogger(): ConsoleLogger
    {
        return $this->getMockBuilder(ConsoleLogger::class)->getMock();
    }

    private function createRunResult(): RunResult
    {
        return new RunResult('PRJ', 1, true, null);
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

    private function createTestEvent(?string $className = null): TestEvent
    {
        $test = $this->createTest($className);
        $event = $this->getMockBuilder(TestEvent::class)
            ->setConstructorArgs([$test])->getMock();
        $event->method('getTest')->willReturn($test);

        return $event;
    }

}
