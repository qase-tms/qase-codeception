<?php

declare(strict_types=1);

namespace Tests;

use Codeception\Event\FailEvent;
use Codeception\Event\TestEvent;
use Codeception\Test\Unit;
use PHPUnit\Framework\TestCase;
use Qase\Codeception\RunResultCollection;
use Qase\PhpClientUtils\Config;
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
        $runResult = $this->createMock(RunResult::class);
        $runResult->expects($this->once())
            ->method('addResult')
            ->with(
                $this->callback(function ($result) use ($expected) {
                    return isset($result['defect']) && $result['defect'] === $expected;
                })
            );

        $runResultCollection = $this->createRunResultCollection($runResult);
        $runResultCollection->add($status, $this->createUnitTestEvent());
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
        $runResult = null;
        $isReportingEnabled = false;
        $runResultCollection = $this->createRunResultCollection($runResult, $isReportingEnabled);
        $runResultCollection->add('failed', $this->createUnitTestEvent());

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
        $runResult = $runResultCollection->get();
        $this->assertEmpty($runResult->getResults());

        // Act: Add run results to the collection
        $runResultCollection->add('failed', $this->createUnitTestFailEvent(1.0, $stackTraceMessage));
        $runResultCollection->add('passed', $this->createUnitTestEvent(0.375));
        // Assert: Check collection results
        $runResult = $runResultCollection->get();
        $this->assertSame($runResult->getResults(), $expectedResult);
    }

    private function createRunResultCollection(
        ?RunResult       $runResult = null,
        bool             $isReportingEnabled = true,
        ?LoggerInterface $logger = null
    ): RunResultCollection
    {
        $runResult = $runResult ?: new RunResult($this->createStub(Config::class));
        $logger = $logger ?: $this->createMock(ConsoleLogger::class);

        return new RunResultCollection($runResult, $isReportingEnabled, $logger);
    }

    private function createUnitTestEvent(float $time = 1.0): TestEvent
    {
        $event = $this->createStub(TestEvent::class);
        $event->method('getTest')->willReturn($this->createUnitTest());
        $event->method('getTime')->willReturn($time);

        return $event;
    }

    private function createUnitTest()
    {
        $test = $this->getMockBuilder(Unit::class)->setMockClassName('Unit')->getMock();
        $test->method('getName')->willReturn('methodName');

        return $test;
    }

    private function createUnitTestFailEvent(float $time, string $stackTraceMessage): FailEvent
    {
        $event = $this->createStub(FailEvent::class);
        $event->method('getTest')->willReturn($this->createUnitTest());
        $event->method('getTime')->willReturn($time);
        $event->method('getFail')->willReturn(new \Exception($stackTraceMessage));

        return $event;
    }
}
