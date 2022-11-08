<?php

declare(strict_types=1);

namespace Tests;

use Codeception\Event\FailEvent;
use Codeception\Event\TestEvent;
use Codeception\Test\Cept;
use Codeception\Test\Cest;
use Codeception\Test\Test;
use Codeception\Test\TestCaseWrapper;
use Codeception\Test\Unit;
use PHPUnit\Framework\TestCase;
use Qase\Codeception\RunResultCollection;
use Qase\PhpClientUtils\ConsoleLogger;
use Qase\PhpClientUtils\LoggerInterface;
use Qase\PhpClientUtils\RunResult;

class RunResultCollectionTest extends TestCase
{

    /**
     * @dataProvider autoCreateDefectDataProvider
     */
    public function testAutoCreateDefect(string $title, string $status, float $time, bool $expected)
    {
        $runResult = $this->createMock(RunResult::class);
        $runResult->expects($this->once())
            ->method('addResult')
            ->with(
                $this->callback(function ($result) use ($expected) {
                    return isset($result['defect']) && $result['defect'] === $expected;
                })
            );

        $runResultCollection = $this->createRunResultCollection($runResult);
        $runResultCollection->add($status, $this->createTestEvent($this->createUnitTest()));
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
        $runResultCollection = $this->createRunResultCollection(isReportingEnabled: false);
        $runResultCollection->add('failed', $this->createTestEvent($this->createUnitTest()));

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
                'full_test_name' => 'Cest::methodName',
                'stacktrace' => null,
                'defect' => false,
            ],
        ];

        // Act: Initialize empty Collection
        $runResultCollection = $this->createRunResultCollection();
        // Assert: Insure results are empty
        $runResult = $runResultCollection->get();
        $this->assertEmpty($runResult->getResults());

        // Act: Add run results to the collection
        $runResultCollection->add('failed', $this->createUnitTestFailEvent($stackTraceMessage, time: 1.0));
        $runResultCollection->add('passed', $this->createTestEvent($this->createCestTest(), time: 0.375));
        // Assert: Check collection results
        $runResult = $runResultCollection->get();
        $this->assertSame($runResult->getResults(), $expectedResult);
    }

    public function testAddingUnsupportedTestType()
    {
        $logger = $this->createMock(ConsoleLogger::class);
        $logger->expects($this->once())
            ->method('writeln')
            ->with($this->equalTo('The test type is not supported yet: Cept. Skipped.'));

        $runResultCollection = $this->createRunResultCollection(logger: $logger);
        $runResultCollection->add('passed', $this->createTestEvent($this->createCeptTest()));
    }

    private function createRunResultCollection(
        ?RunResult       $runResult = null,
        bool             $isReportingEnabled = true,
        ?LoggerInterface $logger = null
    ): RunResultCollection
    {
        $runResult = $runResult ?: new RunResult(projectCode: 'PRJ', runId: 1, completeRunAfterSubmit: true);
        $logger = $logger ?: $this->createMock(ConsoleLogger::class);

        return new RunResultCollection($runResult, $isReportingEnabled, $logger);
    }

    private function createUnitTest()
    {
        $unitTest = $this->getMockBuilder(Unit::class)->setMockClassName('Unit')->getMock();
        $unitTest->method('dataName')->willReturn('');
        $unitTest->method('getName')->willReturn('getMetadata');
        $test = $this->createStub(TestCaseWrapper::class);
        $test->method('getName')->willReturn('methodName');
        $test->method('getTestCase')->willReturn($unitTest);

        return $test;
    }

    private function createCestTest()
    {
        $testInstance = $this->getMockBuilder(\stdClass::class)->setMockClassName('Cest')
            ->addMethods(['string'])->getMock();

        $test = $this->createStub(Cest::class);
        $test->method('getTestMethod')->willReturn('methodName');
        $test->method('getTestInstance')->willReturn($testInstance);

        return $test;
    }

    private function createCeptTest()
    {
        return $this->getMockBuilder(Cept::class)->setMockClassName('Cept')
            ->disableOriginalConstructor()->getMock();
    }

    private function createTestEvent(Test $test, float $time = 1.0): TestEvent
    {
        $event = $this->createStub(TestEvent::class);
        $event->method('getTest')->willReturn($test);
        $event->method('getTime')->willReturn($time);

        return $event;
    }

    private function createUnitTestFailEvent(string $stackTraceMessage, float $time): FailEvent
    {
        $event = $this->createStub(FailEvent::class);
        $event->method('getTest')->willReturn($this->createUnitTest());
        $event->method('getTime')->willReturn($time);
        $event->method('getFail')->willReturn(new \Exception($stackTraceMessage));

        return $event;
    }
}
