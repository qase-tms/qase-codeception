<?php

declare(strict_types=1);

namespace Qase\Codeception;

use Qase\PhpCommons\Interfaces\ReporterInterface;
use Qase\PhpCommons\Reporters\ReporterFactory;

class SingletonCoreReporter
{
    private static ReporterInterface $instance;

    private function __construct()
    {
    }

    public static function getInstance(): ReporterInterface
    {
        if (!isset(self::$instance)) {
            self::$instance = ReporterFactory::create();
        }
        return self::$instance;
    }
}
