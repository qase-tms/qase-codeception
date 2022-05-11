<?php

namespace Tests\Unit;

class ExampleTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;
    
    public function testSuccess(): void
    {
        $this->assertTrue(true);
    }

    public function testSkipped(): void
    {
        $this->markTestSkipped();
    }

    /**
     * @qaseId 8
     */
    public function testFail(): void
    {
        $this->assertTrue(false);
    }
}
