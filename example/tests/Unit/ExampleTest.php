<?php

namespace Tests\Unit;

use Qase\Codeception\Attributes\QaseId;
use Tests\Support\UnitTester;

class ExampleTest extends \Codeception\Test\Unit
{

    public function testSuccess(): void
    {
        $this->assertTrue(true);
    }

    public function testSkipped(): void
    {
        $this->markTestSkipped();
    }

    #[QaseId(8)]
    public function testFail(): void
    {
        $this->assertTrue(false);
    }
}
