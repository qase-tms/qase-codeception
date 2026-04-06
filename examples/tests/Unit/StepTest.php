<?php

declare(strict_types=1);

namespace Tests\Unit;

use Qase\Codeception\Attributes\QaseId;
use Qase\Codeception\Attributes\Suite;
use Tests\Support\UnitTester;

class StepTest extends \Codeception\Test\Unit
{
    protected UnitTester $tester;

    #[QaseId(13)]
    #[Suite('Steps')]
    public function testWithPassingSteps(): void
    {
        $this->tester->assertSame(1, 1);
        $this->tester->assertNotEmpty('hello');
        $this->tester->assertTrue(true);
    }

    #[QaseId(14)]
    #[Suite('Steps')]
    public function testWithFailingStep(): void
    {
        $this->tester->assertTrue(true);
        $this->tester->assertSame(1, 2);
    }
}
