<?php

declare(strict_types=1);

namespace Qase\Codeception;

use Codeception\Event\FailEvent;
use Codeception\Event\PrintResultEvent;
use Codeception\Event\StepEvent;
use Codeception\Event\SuiteEvent;
use Codeception\Event\TestEvent;
use Codeception\Extension;
use Codeception\Events;
use Qase\Codeception\Attributes\AttributeParser;
use Qase\Codeception\Attributes\AttributeParserInterface;
use Qase\Codeception\Attributes\AttributeReader;
use Qase\Codeception\Models\Metadata;
use Qase\PhpCommons\Interfaces\ReporterInterface;
use Qase\PhpCommons\Loggers\Logger;
use Qase\PhpCommons\Models\Relation;
use Qase\PhpCommons\Models\Result;
use Qase\PhpCommons\Models\Step;
use Qase\PhpCommons\Utils\Signature;

class Reporter extends Extension
{
    private const STATUS_PASSED = 'passed';
    private const STATUS_FAILED = 'failed';
    private const STATUS_SKIPPED = 'skipped';
    private const STATUS_INVALID = 'invalid';

    private bool $isInit = false;
    private ReporterInterface $reporter;
    private ?Result $currentResult = null;
    private AttributeParserInterface $attributeParser;

    public static array $events = [
        Events::MODULE_INIT => 'moduleInit',
        Events::TEST_START => 'startTest',
        Events::STEP_AFTER => 'afterStep',
        Events::TEST_FAIL => 'failTest',
        Events::TEST_ERROR => 'errorTest',
        Events::TEST_PARSED => 'parsedTest',
        Events::TEST_INCOMPLETE => 'incompleteTest',
        Events::TEST_SKIPPED => 'skippedTest',
        Events::TEST_WARNING => 'warningTest',
        Events::TEST_USELESS => 'uselessTest',
        Events::TEST_SUCCESS => 'successTest',
        Events::TEST_END => 'endTest',
        Events::RESULT_PRINT_AFTER => 'sendResults',
    ];

    public function _initialize(): void
    {
        parent::_initialize();
        $this->reporter = SingletonCoreReporter::getInstance();
        $this->attributeParser = new AttributeParser(new Logger(), new AttributeReader());
    }

    public function moduleInit(SuiteEvent $event): void
    {
        if (!$this->isInit) {
            $this->isInit = true;
            $this->reporter->startRun();
        }
    }

    public function startTest(TestEvent $event): void
    {
        $test = $event->getTest();
        $className = $this->getClassName($event);
        $fields = $test->getReportFields();

        // Extract class name from report fields if available
        if (isset($fields['class'])) {
            $className = $fields['class'];
        }

        $metadata = $this->attributeParser->parseAttribute($className, $test->getName());
        $relation = $this->createRelation($metadata, $event);

        $result = new Result();
        $result->title = $metadata->title ?? $test->getName();
        $result->params = $metadata->parameters;
        $result->fields = $metadata->fields;
        $result->signature = $this->createSignature($event, $metadata->qaseIds, $metadata->parameters);
        $result->execution->thread = "main";
        $result->relations = $relation;

        if (!empty($metadata->qaseIds)) {
            $result->testOpsIds = $metadata->qaseIds;
        }

        $this->currentResult = $result;
    }

    public function afterStep(StepEvent $event): void
    {
        $testStep = $event->getStep();
        $step = new Step();
        $step->data->setAction(
            $testStep->getHumanizedActionWithoutArguments() . " " . $testStep->getHumanizedArguments()
        );
        $step->execution->setStatus($testStep->hasFailed() ? self::STATUS_FAILED : self::STATUS_PASSED);
        $this->currentResult->steps[] = $step;
    }

    public function failTest(FailEvent $event): void
    {
        $this->setTestFailed($event);
    }

    public function errorTest(FailEvent $event): void
    {
        $this->setTestFailed($event);
    }

    public function parsedTest(TestEvent $event): void
    {
        print "Test parsed\n";
    }

    public function incompleteTest(FailEvent $event): void
    {
        $this->setTestFailed($event);
    }

    public function skippedTest(FailEvent $event): void
    {
        $this->updateResultWithFailure($event, self::STATUS_SKIPPED);
    }

    public function warningTest(FailEvent $event): void
    {
        $this->setTestFailed($event);
    }

    public function uselessTest(FailEvent $event): void
    {
        $this->setTestFailed($event);
    }

    public function successTest(TestEvent $event): void
    {
        $this->currentResult->execution->setStatus(self::STATUS_PASSED);
    }

    public function endTest(TestEvent $event): void
    {
        $this->currentResult->execution->finish();
        $this->reporter->addResult($this->currentResult);
        $this->currentResult = null;
    }

    public function sendResults(PrintResultEvent $event): void
    {
        $this->reporter->completeRun();
    }

    private function createSignature(TestEvent $event, ?array $ids = null, ?array $params = null): string
    {
        $suites = explode('\\', $event->getTest()->getSignature());
        return Signature::generateSignature($ids, $suites, $params);
    }

    private function getSuites(TestEvent $event): array
    {
        $normalized = str_replace(':', '\\', $event->getTest()->getSignature());
        return explode('\\', $normalized);
    }

    private function setTestFailed(FailEvent $event): void
    {
        $status = $this->determineFailureStatus($event);
        $this->updateResultWithFailure($event, $status);
    }

    private function updateResultWithFailure(FailEvent $event, string $status): void
    {
        $this->currentResult->execution->setStatus($status);
        $this->currentResult->execution->setStackTrace($event->getFail()->getTraceAsString());
        $this->currentResult->message .= $event->getFail()->getMessage();
    }

    private function getClassName(TestEvent $event): string
    {
        $parts = explode(":", $event->getTest()->getSignature())[0];
        $testClass = explode("\\", $parts);
        return end($testClass);
    }

    private function createRelation(Metadata $metadata, TestEvent $event): Relation
    {
        $relation = new Relation();

        if (empty($metadata->suites)) {
            foreach ($this->getSuites($event) as $suite) {
                $relation->addSuite($suite);
            }
        } else {
            foreach ($metadata->suites as $suite) {
                $relation->addSuite($suite);
            }
        }

        return $relation;
    }

    /**
     * Determines the appropriate status for a failed test based on the type of exception.
     * Returns 'failed' for assertion failures and 'invalid' for other types of errors.
     */
    private function determineFailureStatus(FailEvent $event): string
    {
        $exception = $event->getFail();
        
        // Check if it's an assertion failure
        if ($this->isAssertionFailure($exception)) {
            return self::STATUS_FAILED;
        }
        
        // All other types of errors are considered invalid
        return self::STATUS_INVALID;
    }

    /**
     * Checks if the exception is an assertion failure.
     * This includes PHPUnit's AssertionFailedError and PHP's AssertionError.
     */
    private function isAssertionFailure(\Throwable $exception): bool
    {
        // Check for PHPUnit's AssertionFailedError and its subclasses
        if ($exception instanceof \PHPUnit\Framework\AssertionFailedError) {
            return true;
        }
        
        // Check for PHP's AssertionError
        if ($exception instanceof \AssertionError) {
            return true;
        }
        
        // Check by class name for additional safety
        $className = get_class($exception);
        if (str_contains($className, 'AssertionFailedError') || 
            str_contains($className, 'AssertionError')) {
            return true;
        }
        
        return false;
    }
}
