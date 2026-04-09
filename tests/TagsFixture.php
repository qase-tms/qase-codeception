<?php

declare(strict_types=1);

namespace Tests;

use Qase\Codeception\Attributes\Field;
use Qase\Codeception\Attributes\QaseId;
use Qase\Codeception\Attributes\Suite;
use Qase\Codeception\Attributes\Tags;
use Qase\Codeception\Attributes\Title;

class TagsFixture
{
    #[Tags('smoke', 'regression')]
    public function testWithTags(): void {}

    public function testWithoutTags(): void {}

    #[Tags('smoke')]
    #[Tags('regression')]
    public function testWithMultipleTags(): void {}

    #[QaseId(100)]
    #[Title('Custom title')]
    #[Suite('Auth')]
    #[Field('severity', 'high')]
    #[Tags('smoke', 'regression')]
    public function testWithAll(): void {}
}
