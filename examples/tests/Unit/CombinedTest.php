<?php

declare(strict_types=1);

namespace Tests\Unit;

use Qase\Codeception\Attributes\Field;
use Qase\Codeception\Attributes\Parameter;
use Qase\Codeception\Attributes\QaseId;
use Qase\Codeception\Attributes\Suite;
use Qase\Codeception\Attributes\Title;
use Tests\Support\UnitTester;

class CombinedTest extends \Codeception\Test\Unit
{
    protected UnitTester $tester;

    #[QaseId(17)]
    #[Title('Full metadata test')]
    #[Suite('E2E')]
    #[Suite('Combined')]
    #[Field('severity', 'critical')]
    #[Field('priority', 'high')]
    #[Parameter('user', 'admin')]
    #[Parameter('role', 'superuser')]
    public function testFullMetadata(): void
    {
        $this->tester->assertSame('admin', 'admin');
        $this->tester->assertTrue(true);
    }

    #[QaseId(18)]
    #[Title('Partial metadata')]
    #[Suite('Combined')]
    #[Field('layer', 'integration')]
    public function testPartialMetadata(): void
    {
        $this->assertTrue(true);
    }
}
