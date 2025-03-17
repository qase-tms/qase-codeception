<?php

namespace Qase\Codeception\Attributes;

use Qase\Codeception\Models\Metadata;

interface AttributeParserInterface
{
    public function parseAttribute(string $className, string $methodName): Metadata;
}
