<?php

declare(strict_types=1);

namespace Qase\Codeception;

use Codeception\Event\FailEvent;
use Codeception\Event\TestEvent;
use Codeception\Extension;
use Codeception\Events;
use Codeception\Test\Cest;
use Codeception\Test\Unit;
use Qase\Client\ApiException;
use Qase\PhpClientUtils\Config;
use Qase\PhpClientUtils\ConsoleLogger;
use Qase\PhpClientUtils\Repository;
use Qase\PhpClientUtils\ResultHandler;
use Qase\PhpClientUtils\ResultsConverter;
use Qase\PhpClientUtils\RunResult;

class Reporter extends Extension
{
    private const ROOT_SUITE_TITLE = 'Codeception tests';

    private const PASSED = 'passed';
    private const SKIPPED = 'skipped';
    private const FAILED = 'failed';

    private RunResult $runResult;
    private Repository $repo;
    private ResultHandler $resultHandler;
    private ConsoleLogger $logger;
    private Config $reporterConfig;
    private HeaderManager $headerManager;

    public static array $events = [
        Events::RESULT_PRINT_AFTER => 'afterSuite',


        Events::TEST_FAIL => 'fail',
        Events::TEST_SUCCESS => 'success',
        Events::TEST_SKIPPED => 'skipped',
        Events::TEST_ERROR => 'error',
    ];

    public function _initialize(): void
    {
        parent::_initialize();

        $this->logger = new ConsoleLogger();
        $this->reporterConfig = new Config();
        $resultsConverter = new ResultsConverter($this->logger);

        if (!$this->reporterConfig->isReportingEnabled()) {
            $this->logger->writeln('Reporting to Qase.io is disabled. Set the environment variable QASE_REPORT=1 to enable it.');
            return;
        }
        $this->reporterConfig->validate();

        $this->headerManager = new HeaderManager();
        $this->repo = new Repository();
        $this->resultHandler = new ResultHandler($this->repo, $resultsConverter, $this->logger);

        $this->repo->init(
            $this->reporterConfig,
            $this->headerManager->getClientHeaders()
        );

        $runId = $this->reporterConfig->getRunId();
        if (!$runId) {
            $runId = $this->resultHandler->createRunId($this->reporterConfig->getProjectCode(), $this->reporterConfig->getEnvironmentId());
            putenv('QASE_RUN_ID=' . $runId);
        }

        $this->runResult = new RunResult(
            $this->reporterConfig->getProjectCode(),
            $runId,
            $this->reporterConfig->getCompleteRunAfterSubmit(),
            $this->reporterConfig->getEnvironmentId(),
        );

        $this->validateProjectCode();
        $this->validateEnvironmentId();
    }

    public function afterSuite(\Codeception\Event\PrintResultEvent $event): void
    {
        if (!$this->reporterConfig->isReportingEnabled()) {
            return;
        }

        try {
            $this->resultHandler->handle(
                $this->runResult,
                $this->reporterConfig->getRootSuiteTitle() ?: self::ROOT_SUITE_TITLE,
            );
        } catch (\Exception $e) {
            $this->logger->writeln('An exception occurred');
            $this->logger->writeln($e->getMessage());

            return;
        }
    }

    public function success(TestEvent $event): void
    {
        $this->accumulateTestResult(self::PASSED, $event);
    }

    public function fail(FailEvent $event): void
    {
        $this->accumulateTestResult(self::FAILED, $event);
    }

    public function error(FailEvent $event): void
    {
        $this->accumulateTestResult(self::FAILED, $event);
    }

    public function skipped(TestEvent $event): void
    {
        $this->accumulateTestResult(self::SKIPPED, $event);
    }

    private function accumulateTestResult(string $status, TestEvent $event): void
    {
        if (!$this->reporterConfig->isReportingEnabled()) {
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

        if ($status === self::FAILED && $event instanceof FailEvent) {
            $message = $event->getFail()->getMessage() ?: $event->getFail()->getTraceAsString();
        }

        $this->runResult->addResult([
            'status' => $status,
            'time' => $event->getTime(),
            'full_test_name' => $class . '::' . $method,
            'stacktrace' => $message ?? null,
        ]);
    }

    private function validateProjectCode(): void
    {
        try {
            $this->logger->write("checking if project '{$this->runResult->getProjectCode()}' exists... ");

            $this->repo->getProjectsApi()->getProject($this->runResult->getProjectCode());

            $this->logger->writeln('OK', '');
        } catch (ApiException $e) {
            $this->logger->writeln("could not find project '{$this->runResult->getProjectCode()}'");

            throw $e;
        }
    }

    private function validateEnvironmentId(): void
    {
        if ($this->reporterConfig->getEnvironmentId() === null) {
            return;
        }

        try {
            $this->logger->write("checking if Environment Id '{$this->reporterConfig->getEnvironmentId()}' exists... ");

            $this->repo->getEnvironmentsApi()->getEnvironment($this->runResult->getProjectCode(), $this->reporterConfig->getEnvironmentId());

            $this->logger->writeln('OK', '');
        } catch (ApiException $e) {
            $this->logger->writeln("could not find Environment Id '{$this->reporterConfig->getEnvironmentId()}'");

            throw $e;
        }
    }
}
