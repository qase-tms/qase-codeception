<?php

declare(strict_types=1);

namespace Qase\Codeception;

use Codeception\Event\FailEvent;
use Codeception\Event\TestEvent;
use Codeception\Test\Cest;
use Codeception\Test\TestCaseWrapper;
use Qase\PhpClientUtils\LoggerInterface;
use Qase\PhpClientUtils\RunResult;

class RunResultCollection
{
    public function __construct(
        private RunResult       $runResult,
        private bool            $isReportingEnabled,
        private LoggerInterface $logger
    )
    {
    }

    public function get(): RunResult
    {
        return $this->runResult;
    }

    public function add(string $status, TestEvent $event): void
    {
        if (!$this->isReportingEnabled) {
            return;
        }

        $test = $event->getTest();

        switch (true) {
            case $test instanceof Cest:
                $class = get_class($test->getTestInstance());
                $method = $test->getTestMethod();
                break;
            case $test instanceof TestCaseWrapper:
                $testCase = $test->getTestCase();
                $class = get_class($testCase);
                $method = $test->getName();
                break;
            default:
                $this->logger->writeln(sprintf('The test type is not supported yet: %s. Skipped.', get_class($test)));
                return;
        }

        if ($status === Reporter::FAILED && $event instanceof FailEvent) {
            $message = $event->getFail()->getMessage() ?: $event->getFail()->getTraceAsString();
        }

        $this->runResult->addResult([
            'status' => $status,
            'time' => $event->getTime(),
            'full_test_name' => $class . '::' . $method,
            'stacktrace' => $message ?? null,
            'defect' => $status === Reporter::FAILED,
        ]);
    }
}
