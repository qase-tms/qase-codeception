<?php

namespace Qase\Codeception\Attributes;

interface TitleAttributeInterface extends AttributeInterface
{
    public function getValue(): string;
}
