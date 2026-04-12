<?php

namespace Tests\Feature\Commands;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MigrateFilesToStorageTest extends TestCase
{
    private string $testSlug = 'backend/images/admin/profile';

    protected function setUp(): void
    {
        parent::setUp();
        // Clean up any leftover test files from previous runs
        File::deleteDirectory(public_path($this->testSlug));
        File::deleteDirectory(storage_path('app/public/' . $this->testSlug));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(public_path($this->testSlug));
        File::deleteDirectory(storage_path('app/public/' . $this->testSlug));
        parent::tearDown();
    }

    /** @test */
    public function it_copies_files_from_public_to_storage(): void
    {
        // Arrange: place a file in public/
        $sourceDir = public_path($this->testSlug);
        File::makeDirectory($sourceDir, 0755, true);
        File::put($sourceDir . '/test-photo.jpg', 'fake-image-content');

        // Act
        $this->artisan('files:migrate-to-storage')->assertExitCode(0);

        // Assert: file exists in storage
        $this->assertFileExists(storage_path('app/public/' . $this->testSlug . '/test-photo.jpg'));
    }

    /** @test */
    public function it_preserves_original_files_in_public(): void
    {
        // Arrange
        $sourceDir = public_path($this->testSlug);
        File::makeDirectory($sourceDir, 0755, true);
        File::put($sourceDir . '/test-logo.png', 'fake-logo-content');

        // Act
        $this->artisan('files:migrate-to-storage')->assertExitCode(0);

        // Assert: original still present
        $this->assertFileExists($sourceDir . '/test-logo.png');
    }

    /** @test */
    public function it_skips_files_already_present_in_destination(): void
    {
        // Arrange: put file in both source and destination
        $sourceDir = public_path($this->testSlug);
        $destDir = storage_path('app/public/' . $this->testSlug);
        File::makeDirectory($sourceDir, 0755, true);
        File::makeDirectory($destDir, 0755, true);
        File::put($sourceDir . '/existing.jpg', 'original-content');
        File::put($destDir . '/existing.jpg', 'already-there-content');

        // Act
        $this->artisan('files:migrate-to-storage')->assertExitCode(0);

        // Assert: destination file content is unchanged (not overwritten)
        $this->assertEquals('already-there-content', file_get_contents($destDir . '/existing.jpg'));
    }
}
