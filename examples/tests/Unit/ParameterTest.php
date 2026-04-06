<?php

declare(strict_types=1);

namespace Tests\Unit;

use Qase\Codeception\Attributes\Field;
use Qase\Codeception\Attributes\Parameter;
use Qase\Codeception\Attributes\QaseId;
use Tests\Support\UnitTester;

class ParameterTest extends \Codeception\Test\Unit
{
    protected UnitTester $tester;

    #[QaseId(10)]
    #[Parameter('browser', 'chrome')]
    public function testWithSingleParameter(): void
    {
        $this->assertTrue(true);
    }

    #[QaseId(11)]
    #[Parameter('browser', 'firefox')]
    #[Parameter('os', 'linux')]
    public function testWithMultipleParameters(): void
    {
        $this->assertSame('firefox', 'firefox');
    }

    #[QaseId(12)]
    #[Parameter('env', 'production')]
    #[Field('severity', 'high')]
    public function testWithParametersAndFields(): void
    {
        $this->assertNotNull('value');
    }
}
