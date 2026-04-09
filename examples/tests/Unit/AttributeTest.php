<?php

declare(strict_types=1);

namespace Tests\Unit;

use Qase\Codeception\Attributes\Field;
use Qase\Codeception\Attributes\QaseId;
use Qase\Codeception\Attributes\QaseIds;
use Qase\Codeception\Attributes\Suite;
use Qase\Codeception\Attributes\Tags;
use Qase\Codeception\Attributes\Title;
use Tests\Support\UnitTester;

#[Tags("smoke")]
class AttributeTest extends \Codeception\Test\Unit
{
    protected UnitTester $tester;

    #[QaseId(1)]
    public function testSimplePass(): void
    {
        $this->assertTrue(true);
    }

    #[QaseId(2)]
    public function testSimpleFail(): void
    {
        $this->assertSame(1, 2, 'Expected values to match');
    }

    #[QaseId(3)]
    public function testError(): void
    {
        throw new \RuntimeException('Unexpected runtime error');
    }

    #[QaseId(4)]
    public function testSkip(): void
    {
        $this->markTestSkipped('Skipped for demonstration');
    }

    #[QaseId(5)]
    #[Title('Custom test title')]
    public function testWithTitle(): void
    {
        $this->assertNotEmpty('hello');
    }

    #[QaseIds([6, 7])]
    public function testWithMultipleIds(): void
    {
        $this->assertIsString('test');
    }

    #[QaseId(8)]
    #[Suite('Authentication')]
    #[Suite('Smoke')]
    public function testWithSuites(): void
    {
        $this->assertGreaterThan(0, 1);
    }

    #[QaseId(9)]
    #[Field('severity', 'critical')]
    #[Field('priority', 'high')]
    #[Field('layer', 'unit')]
    #[Tags('regression')]
    public function testWithFields(): void
    {
        $this->assertCount(3, [1, 2, 3]);
    }
}
