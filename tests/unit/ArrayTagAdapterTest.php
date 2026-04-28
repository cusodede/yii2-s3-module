<?php
declare(strict_types = 1);

namespace unit;

use Codeception\Test\Unit;
use cusodede\s3\models\ArrayTagAdapter;

/**
 * Test suite for ArrayTagAdapter.
 * Pins the documented tag-shape contract — including the non-obvious numeric-key
 * promotion where a list entry like ['foo'] becomes the self-keyed tag ['foo' => 'foo'].
 */
class ArrayTagAdapterTest extends Unit {

    /**
     * No-arg construction yields no tags.
     * @return void
     */
    public function testEmptyConstruction():void {
        $this::assertSame([], new ArrayTagAdapter()->getTags());
    }

    /**
     * Explicit null and empty array are equivalent: both produce an empty tag map.
     * @return void
     */
    public function testNullAndEmptyArrayEquivalent():void {
        $this::assertSame([], new ArrayTagAdapter(null)->getTags());
        $this::assertSame([], new ArrayTagAdapter([])->getTags());
    }

    /**
     * String key/value pairs round-trip unchanged.
     * @return void
     */
    public function testStringKeyValuePairs():void {
        $adapter = new ArrayTagAdapter(['env' => 'test', 'team' => 'platform']);
        $this::assertSame(['env' => 'test', 'team' => 'platform'], $adapter->getTags());
    }

    /**
     * A list-style entry (auto-numeric key) is promoted to a self-keyed tag:
     * ['solo'] becomes ['solo' => 'solo']. This is the non-obvious behaviour
     * S3ModuleTest::testTagsBinding exercises indirectly.
     * @return void
     */
    public function testNumericKeyPromotion():void {
        $adapter = new ArrayTagAdapter(['solo']);
        $this::assertSame(['solo' => 'solo'], $adapter->getTags());
    }

    /**
     * An explicit integer key is discarded; only the value survives, mapped to itself.
     * @return void
     */
    public function testExplicitNumericKeyIsDropped():void {
        $adapter = new ArrayTagAdapter([42 => 'answer']);
        $this::assertSame(['answer' => 'answer'], $adapter->getTags());
    }

    /**
     * Mixed list- and map-style entries combine into one tag map.
     * @return void
     */
    public function testMixedStyleConstruction():void {
        $adapter = new ArrayTagAdapter(['env' => 'prod', 'critical', 'team' => 'platform',]);
        $this::assertSame(['env' => 'prod', 'critical' => 'critical', 'team' => 'platform',], $adapter->getTags());
    }

    /**
     * setTag with a null value defaults the value to the key.
     * @return void
     */
    public function testSetTagNullValueDefaultsToKey():void {
        $adapter = new ArrayTagAdapter();
        $adapter->setTag('orphan');
        $this::assertSame(['orphan' => 'orphan'], $adapter->getTags());
    }

    /**
     * setTag with no value argument defaults the value to the key.
     * @return void
     */
    public function testSetTagOmittedValueDefaultsToKey():void {
        $adapter = new ArrayTagAdapter();
        $adapter->setTag('flag');
        $this::assertSame(['flag' => 'flag'], $adapter->getTags());
    }

    /**
     * setTag overwrites an existing entry.
     * @return void
     */
    public function testSetTagOverwritesExisting():void {
        $adapter = new ArrayTagAdapter(['env' => 'staging']);
        $adapter->setTag('env', 'production');
        $this::assertSame(['env' => 'production'], $adapter->getTags());
    }

    /**
     * addTag returns true and stores the tag when the key is absent.
     * @return void
     */
    public function testAddTagReturnsTrueWhenAbsent():void {
        $adapter = new ArrayTagAdapter();
        $this::assertTrue($adapter->addTag('env', 'prod'));
        $this::assertSame(['env' => 'prod'], $adapter->getTags());
    }

    /**
     * addTag returns false and leaves the existing value untouched (does not overwrite).
     * @return void
     */
    public function testAddTagReturnsFalseWhenPresent():void {
        $adapter = new ArrayTagAdapter(['env' => 'staging']);
        $this::assertFalse($adapter->addTag('env', 'production'));
        $this::assertSame(['env' => 'staging'], $adapter->getTags());
    }

    /**
     * tagSet returns the AWS-shaped [['Key' => ..., 'Value' => ...], ...] required by
     * putObjectTagging, preserving insertion order.
     * @return void
     */
    public function testTagSetShape():void {
        $adapter = new ArrayTagAdapter(['env' => 'prod', 'critical']);
        $this::assertSame([['Key' => 'env', 'Value' => 'prod'], ['Key' => 'critical', 'Value' => 'critical'],], $adapter->tagSet());
    }

    /**
     * tagSet on an empty adapter returns an empty array.
     * @return void
     */
    public function testTagSetEmpty():void {
        $this::assertSame([], new ArrayTagAdapter()->tagSet());
    }

    /**
     * __toString produces an http_build_query string used as the Tagging header value
     * in putObject; ampersands and spaces in values are URL-encoded.
     * @return void
     */
    public function testToStringIsUrlEncoded():void {
        $adapter = new ArrayTagAdapter(['env' => 'prod', 'team' => 'platform & search']);
        $this::assertSame('env=prod&team=platform+%26+search', (string) $adapter);
    }

    /**
     * __toString on an empty adapter returns an empty string.
     * @return void
     */
    public function testToStringEmpty():void {
        $this::assertSame('', (string) new ArrayTagAdapter());
    }
}
