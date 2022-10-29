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
        $runResult = $this->getMockBuilder(RunResult::class)
            ->setConstructorArgs([$this->createConfig()])
            ->getMock();
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
        $isReportingEnabled = false;
        $runResultCollection = $this->createRunResultCollection(null, $isReportingEnabled);
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
        $runResultWithoutResults = $runResultCollection->get();
        $this->assertEmpty($runResultWithoutResults->getResults());

        // Act: Add run results to the collection
        $runResultCollection->add('failed', $this->createUnitTestFailEvent(1.0, $stackTraceMessage));
        $runResultCollection->add('passed', $this->createUnitTestEvent(0.375));
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
        $runId = 1;
        return new RunResult($this->createConfig('PRJ', $runId));
    }

    private function createLogger(): ConsoleLogger
    {
        return $this->getMockBuilder(ConsoleLogger::class)->getMock();
    }

    private function createUnitTestEvent(float $time = 1.0): TestEvent
    {
        $test = $this->createUnitTest();
        $event = $this->getMockBuilder(TestEvent::class)
            ->setConstructorArgs([$test, $time])->getMock();
        $event->method('getTest')->willReturn($test);
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
        $test = $this->createUnitTest();
        $exception = new \Exception($stackTraceMessage);
        $event = $this->getMockBuilder(FailEvent::class)
            ->setConstructorArgs([$test, $time, $exception])->getMock();
        $event->method('getTest')->willReturn($test);
        $event->method('getTime')->willReturn($time);
        $event->method('getFail')->willReturn($exception);

        return $event;
    }

    private function createConfig(string $projectCode = 'PRJ', ?int $runId = null): Config
    {
        $config = $this->getMockBuilder(Config::class)
            ->setConstructorArgs(['Reporter'])->getMock();
        $config->method('getRunId')->willReturn($runId);
        $config->method('getProjectCode')->willReturn($projectCode);
        $config->method('getEnvironmentId')->willReturn(null);

        return $config;
    }
}
