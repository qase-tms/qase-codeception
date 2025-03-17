<?php

namespace Qase\Codeception\Attributes;

interface QaseIdAttributeInterface extends AttributeInterface
{
    public function getValue(): int;
}
