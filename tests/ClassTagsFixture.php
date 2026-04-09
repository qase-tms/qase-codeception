<?php

declare(strict_types=1);

namespace Tests;

use Qase\Codeception\Attributes\Tags;

#[Tags('smoke')]
class ClassTagsFixture
{
    #[Tags('regression')]
    public function testWithMethodTags(): void {}

    public function testWithoutTags(): void {}
}
