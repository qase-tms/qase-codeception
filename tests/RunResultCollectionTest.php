<?php

namespace Tests;

use Codeception\Event\TestEvent;
use Codeception\Test\Unit;
use PHPUnit\Framework\TestCase;
use Qase\Codeception\RunResultCollection;
use Qase\PhpClientUtils\ConsoleLogger;
use Qase\PhpClientUtils\RunResult;

class RunResultCollectionTest extends TestCase
{

    protected function setUp(): void
    {
        //Compatibility with Symfony 5
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

        $logger = $this->getMockBuilder(ConsoleLogger::class)->getMock();

        $runResultCollection = new RunResultCollection($runResult, true, $logger);
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
}
