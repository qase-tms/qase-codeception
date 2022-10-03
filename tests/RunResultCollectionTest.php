<?php

namespace Tests;

use Codeception\Event\FailEvent;
use Codeception\Event\TestEvent;
use Codeception\Test\Cest;
use Codeception\Test\Unit;
use PHPUnit\Framework\TestCase;
use Qase\Codeception\RunResultCollection;
use Qase\PhpClientUtils\ConsoleLogger;
use Qase\PhpClientUtils\RunResult;

class RunResultCollectionTest extends TestCase
{

    protected function setUp(): void
    {
        // Codeception 4 dependencies fix. Original comment: Compatibility with Symfony 5.
        if (!class_exists('Symfony\Component\EventDispatcher\Event') && class_exists('Symfony\Contracts\EventDispatcher\Event')) {
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

        $test = $this->getMockBuilder(Unit::class)->getMock();
        $event = $this->getMockBuilder(TestEvent::class)
            ->setConstructorArgs([$test])->getMock();
        $event->method('getTest')->willReturn($test);

        $runResultCollection = new RunResultCollection($runResult, true, $this->createLogger());
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
        $runResult = new RunResult('PRJ', 1, true, null);
        $runResultCollection = new RunResultCollection($runResult, true, $this->createLogger());
        $this->assertInstanceOf(RunResult::class, $runResultCollection->get());
    }

    public function testAddDoesNothingWhenReportingIsDisabled()
    {
        $runResult = new RunResult('PRJ', 1, true, null);
        $runResultCollection = new RunResultCollection($runResult, false, $this->createLogger());

        $test = $this->getMockBuilder(Unit::class)->getMock();
        $event = $this->getMockBuilder(TestEvent::class)
            ->setConstructorArgs([$test])->getMock();
        $event->method('getTest')->willReturn($test);

        $runResultCollection->add('failed', $event);

        $runResultWithoutResults = $runResultCollection->get();
        $this->assertEmpty($runResultWithoutResults->getResults());
    }

    public function testAddCorrectlyAddsResult()
    {
        $runResult = new RunResult('PRJ', 1, true, null);
        $runResultCollection = new RunResultCollection($runResult, true, $this->createLogger());
        $runResultWithoutResults = $runResultCollection->get();
        $this->assertEmpty($runResultWithoutResults->getResults());

        $stackTraceMessage = 'Stack trace text';

        $testUnit = $this->getMockBuilder(Unit::class)->setMockClassName('Unit')->getMock();
        $testUnit->method('getName')->willReturn('methodName');
        $exception = new \Exception($stackTraceMessage);
        $eventUnit = $this->getMockBuilder(FailEvent::class)
            ->setConstructorArgs([$testUnit, 1.0, $exception])->getMock();
        $eventUnit->method('getTest')->willReturn($testUnit);
        $eventUnit->method('getFail')->willReturn($exception);
        $eventUnit->method('getTime')->willReturn(1.0);

        // TODO-item: refactor this code
        $testUnit2 = $this->getMockBuilder(Unit::class)->setMockClassName('Unit')->getMock();
        $testUnit2->method('getName')->willReturn('methodName');
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
        $runResult = new RunResult('PRJ', 1, true, null);
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

        $runResultCollection = new RunResultCollection($runResult, true, $logger);
        $runResultCollection->add('passed', $event);
    }

    private function createLogger(): ConsoleLogger
    {
        return $this->getMockBuilder(ConsoleLogger::class)->getMock();
    }

}
