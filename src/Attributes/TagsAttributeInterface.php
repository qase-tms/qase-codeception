<?php

namespace Qase\Codeception\Attributes;

interface TagsAttributeInterface extends AttributeInterface
{
    /**
     * @return string[]
     */
    public function getTags(): array;
}
