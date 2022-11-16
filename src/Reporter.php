<?php

declare(strict_types=1);

namespace Qase\Codeception;

use Codeception\Event\FailEvent;
use Codeception\Event\TestEvent;
use Codeception\Extension;
use Codeception\Events;
use Qase\Client\ApiException;
use Qase\PhpClientUtils\Config;
use Qase\PhpClientUtils\LoggerInterface;
use Qase\PhpClientUtils\ConsoleLogger;
use Qase\PhpClientUtils\NullLogger;
use Qase\PhpClientUtils\Repository;
use Qase\PhpClientUtils\ResultHandler;
use Qase\PhpClientUtils\ResultsConverter;
use Qase\PhpClientUtils\RunResult;

class Reporter extends Extension
{
    private const ROOT_SUITE_TITLE = 'Codeception tests';

    private const PASSED = 'passed';
    private const SKIPPED = 'skipped';
    public const FAILED = 'failed';

    private Repository $repo;
    private ResultHandler $resultHandler;
    private LoggerInterface $logger;
    private Config $reporterConfig;
    private HeaderManager $headerManager;
    private RunResultCollection $runResultCollection;

    public static array $events = [
        Events::RESULT_PRINT_AFTER => 'afterSuite',


        Events::TEST_FAIL => 'fail',
        Events::TEST_SUCCESS => 'success',
        Events::TEST_SKIPPED => 'skipped',
        Events::TEST_ERROR => 'error',
    ];

    /**
     * @throws ApiException
     */
    public function _initialize(): void
    {
        parent::_initialize();

        $this->reporterConfig = new Config('Codeception');
        if ($this->reporterConfig->isLoggingEnabled()) {
            $this->logger = new ConsoleLogger();
        } else {
            $this->logger = new NullLogger();
        }

        $runResult = new RunResult($this->reporterConfig);
        $this->runResultCollection = new RunResultCollection(
            $runResult,
            $this->reporterConfig->isReportingEnabled(),
            $this->logger
        );

        if (!$this->reporterConfig->isReportingEnabled()) {
            $this->logger->writeln('Reporting to Qase.io is disabled. Set the environment variable QASE_REPORT=1 to enable it.');
            return;
        }

        $this->repo = new Repository();
        $resultsConverter = new ResultsConverter($this->logger);
        $this->resultHandler = new ResultHandler($this->repo, $resultsConverter, $this->logger);

        $this->headerManager = new HeaderManager();
        $this->repo->init(
            $this->reporterConfig,
            $this->headerManager->getClientHeaders()
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
                $this->runResultCollection->get(),
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
        $this->runResultCollection->add(self::PASSED, $event);
    }

    public function fail(FailEvent $event): void
    {
        $this->runResultCollection->add(self::FAILED, $event);
    }

    public function error(FailEvent $event): void
    {
        $this->runResultCollection->add(self::FAILED, $event);
    }

    public function skipped(TestEvent $event): void
    {
        $this->runResultCollection->add(self::SKIPPED, $event);
    }

    /**
     * @throws ApiException
     */
    private function validateProjectCode(): void
    {
        try {
            $this->logger->write("checking if project '{$this->reporterConfig->getProjectCode()}' exists... ");

            $this->repo->getProjectsApi()->getProject($this->reporterConfig->getProjectCode());

            $this->logger->writeln('OK', '');
        } catch (ApiException $e) {
            $this->logger->writeln("could not find project '{$this->reporterConfig->getProjectCode()}'");

            throw $e;
        }
    }

    /**
     * @throws ApiException
     */
    private function validateEnvironmentId(): void
    {
        if ($this->reporterConfig->getEnvironmentId() === null) {
            return;
        }

        try {
            $this->logger->write("checking if Environment Id '{$this->reporterConfig->getEnvironmentId()}' exists... ");

            $this->repo->getEnvironmentsApi()->getEnvironment($this->reporterConfig->getProjectCode(), $this->reporterConfig->getEnvironmentId());

            $this->logger->writeln('OK', '');
        } catch (ApiException $e) {
            $this->logger->writeln("could not find Environment Id '{$this->reporterConfig->getEnvironmentId()}'");

            throw $e;
        }
    }
}
