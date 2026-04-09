<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Qase\Codeception\Attributes\AttributeParser;
use Qase\Codeception\Attributes\AttributeReader;
use Qase\PhpCommons\Loggers\Logger;

class TagsAttributeTest extends TestCase
{
    private AttributeParser $parser;

    protected function setUp(): void
    {
        $this->parser = new AttributeParser(new Logger(), new AttributeReader());
    }

    public function testParseTagsFromSingleAttribute(): void
    {
        $metadata = $this->parser->parseAttribute(TagsFixture::class, 'testWithTags');
        $this->assertSame(['smoke', 'regression'], $metadata->tags);
    }

    public function testMergeClassAndMethodTags(): void
    {
        $metadata = $this->parser->parseAttribute(ClassTagsFixture::class, 'testWithMethodTags');
        $this->assertSame(['smoke', 'regression'], $metadata->tags);
    }

    public function testEmptyTags(): void
    {
        $metadata = $this->parser->parseAttribute(TagsFixture::class, 'testWithoutTags');
        $this->assertSame([], $metadata->tags);
    }

    public function testParseTagsFromMultipleAttributes(): void
    {
        $metadata = $this->parser->parseAttribute(TagsFixture::class, 'testWithMultipleTags');
        $this->assertSame(['smoke', 'regression'], $metadata->tags);
    }

    public function testClassTagsInheritedByMethodWithoutTags(): void
    {
        $metadata = $this->parser->parseAttribute(ClassTagsFixture::class, 'testWithoutTags');
        $this->assertSame(['smoke'], $metadata->tags);
    }

    public function testAllAttributesTogether(): void
    {
        $metadata = $this->parser->parseAttribute(TagsFixture::class, 'testWithAll');
        $this->assertSame('Custom title', $metadata->title);
        $this->assertSame([100], $metadata->qaseIds);
        $this->assertSame(['Auth'], $metadata->suites);
        $this->assertSame(['severity' => 'high'], $metadata->fields);
        $this->assertSame(['smoke', 'regression'], $metadata->tags);
    }
}
