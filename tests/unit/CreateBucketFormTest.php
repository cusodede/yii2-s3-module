<?php

declare(strict_types=1);

namespace unit;

use Codeception\Test\Unit;
use cusodede\s3\forms\CreateBucketForm;
use Throwable;

/**
 * Test suite for CreateBucketForm.
 * Pins the validation rule chain (required, regex, uniqueness against the live
 * S3 bucket listing) and both branches of the inline checkNameUnique validator.
 */
class CreateBucketFormTest extends Unit
{
    /**
     * checkNameUnique returns false and adds no error when the candidate name
     * is not already present in the live bucket list.
     * @return void
     * @throws Throwable
     */
    public function testCheckNameUniquePassesForNewName(): void
    {
        $form = new CreateBucketForm(['name' => 'novel-' . uniqid()]);

        $this::assertFalse($form->checkNameUnique('name'));
        $this::assertEmpty($form->errors);
    }

    /**
     * checkNameUnique returns true and attaches the localized uniqueness error
     * when the name collides with an existing bucket.
     * @return void
     * @throws Throwable
     */
    public function testCheckNameUniqueFailsForExistingBucket(): void
    {
        $form = new CreateBucketForm(['name' => 'testbucket']);

        $this::assertTrue($form->checkNameUnique('name'));
        $this::assertContains('Наименование должно быть уникальным', $form->getErrors('name'));
    }

    /**
     * Empty name fails the `required` rule (subsequent rules short-circuit).
     * @return void
     */
    public function testValidateRequiresName(): void
    {
        $form = new CreateBucketForm();

        $this::assertFalse($form->validate());
        $this::assertArrayHasKey('name', $form->errors);
    }

    /**
     * Names containing characters outside [A-Za-z0-9-] fail the `match` rule.
     * Note: the regex permits uppercase even though S3 itself rejects upper-case
     * bucket names — that mismatch is pre-existing and out of scope here; this
     * test pins the regex contract as written.
     * @return void
     */
    public function testValidateRejectsBadCharacters(): void
    {
        $form = new CreateBucketForm(['name' => 'name with spaces']);

        $this::assertFalse($form->validate());
        $this::assertArrayHasKey('name', $form->errors);
    }

    /**
     * A regex-valid but already-existing name fails validation through the
     * inline checkNameUnique validator (third rule in the chain).
     * @return void
     */
    public function testValidateRejectsClashingName(): void
    {
        $form = new CreateBucketForm(['name' => 'testbucket']);

        $this::assertFalse($form->validate());
        $this::assertContains('Наименование должно быть уникальным', $form->getErrors('name'));
    }

    /**
     * A non-empty, regex-valid, unused name satisfies the entire rule chain.
     * @return void
     */
    public function testValidateAcceptsValidUniqueName(): void
    {
        $form = new CreateBucketForm(['name' => 'novel-' . uniqid()]);

        $this::assertTrue($form->validate());
        $this::assertEmpty($form->errors);
    }
}
