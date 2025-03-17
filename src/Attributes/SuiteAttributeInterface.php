<?php

namespace Qase\Codeception\Attributes;

interface SuiteAttributeInterface extends AttributeInterface
{
    public function getValue(): string;
}
