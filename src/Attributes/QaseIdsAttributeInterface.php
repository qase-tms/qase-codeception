<?php

namespace Qase\Codeception\Attributes;

interface QaseIdsAttributeInterface extends AttributeInterface
{
    public function getValue(): array;
}
