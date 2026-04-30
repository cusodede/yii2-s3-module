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
     * Names containing characters outside the allowed set (lowercase letters,
     * digits, dots, hyphens) fail the `match` rule.
     * @return void
     */
    public function testValidateRejectsBadCharacters(): void
    {
        $form = new CreateBucketForm(['name' => 'name with spaces']);

        $this::assertFalse($form->validate());
        $this::assertArrayHasKey('name', $form->errors);
    }

    /**
     * S3 bucket names must be lowercase. Uppercase is now rejected client-side
     * rather than round-tripping to AWS for an InvalidBucketName response.
     * @return void
     */
    public function testValidateRejectsUppercase(): void
    {
        $form = new CreateBucketForm(['name' => 'INVALID-BUCKET']);

        $this::assertFalse($form->validate());
        $this::assertArrayHasKey('name', $form->errors);
    }

    /**
     * S3 requires bucket names to be at least 3 characters long.
     * @return void
     */
    public function testValidateRejectsTooShort(): void
    {
        $form = new CreateBucketForm(['name' => 'ab']);

        $this::assertFalse($form->validate());
        $this::assertArrayHasKey('name', $form->errors);
    }

    /**
     * S3 caps bucket names at 63 characters.
     * @return void
     */
    public function testValidateRejectsTooLong(): void
    {
        $form = new CreateBucketForm(['name' => str_repeat('a', 64)]);

        $this::assertFalse($form->validate());
        $this::assertArrayHasKey('name', $form->errors);
    }

    /**
     * S3 disallows hyphens at the start of a bucket name.
     * @return void
     */
    public function testValidateRejectsLeadingHyphen(): void
    {
        $form = new CreateBucketForm(['name' => '-bucket']);

        $this::assertFalse($form->validate());
        $this::assertArrayHasKey('name', $form->errors);
    }

    /**
     * S3 disallows hyphens at the end of a bucket name.
     * @return void
     */
    public function testValidateRejectsTrailingHyphen(): void
    {
        $form = new CreateBucketForm(['name' => 'bucket-']);

        $this::assertFalse($form->validate());
        $this::assertArrayHasKey('name', $form->errors);
    }

    /**
     * Lowercase names with embedded dots are valid S3 bucket names — the
     * regex must not accidentally exclude them.
     * @return void
     * @throws Throwable
     */
    public function testValidateAcceptsLowercaseWithDots(): void
    {
        $form = new CreateBucketForm(['name' => 'foo.bar.' . uniqid()]);

        $this::assertTrue($form->validate());
        $this::assertEmpty($form->errors);
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
