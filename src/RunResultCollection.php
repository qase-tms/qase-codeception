<?php

declare(strict_types=1);

namespace Qase\Codeception;

use Codeception\Event\FailEvent;
use Codeception\Event\TestEvent;
use Codeception\Test\Cest;
use Codeception\Test\Unit;
use Qase\PhpClientUtils\LoggerInterface;
use Qase\PhpClientUtils\RunResult;

class RunResultCollection
{
    private RunResult $runResult;
    private LoggerInterface $logger;
    private bool $isReportingEnabled;

    public function __construct(RunResult $runResult, bool $isReportingEnabled, LoggerInterface $logger)
    {
        $this->isReportingEnabled = $isReportingEnabled;
        $this->runResult = $runResult;
        $this->logger = $logger;
    }

    public function add(string $status, TestEvent $event): void
    {
        if (!$this->isReportingEnabled) {
            return;
        }

        $test = $event->getTest();

        switch (true) {
            case $test instanceof Cest:
                $class = get_class($test->getTestClass());
                $method = $test->getTestMethod();
                break;
            case $test instanceof Unit:
                $class = get_class($test);
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

    public function get(): RunResult
    {
        return $this->runResult;
    }
}
