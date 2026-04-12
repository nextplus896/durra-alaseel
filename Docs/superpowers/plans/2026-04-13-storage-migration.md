# Storage Migration Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Move all uploaded dynamic files from `/public/` to `storage/app/public/` by modifying 6 helper functions and adding one Artisan migration command.

**Architecture:** The project's entire file I/O is centralized through `get_files_path()` (write path), `files_asset_path()` (public URL), `create_asset_dir()` (directory creation), and two inline functions. Changing these 6 functions is sufficient — all 30+ controllers and 6+ models inherit the change automatically. A new `MigrateFilesToStorage` Artisan command copies existing files and creates the `public/storage` symlink.

**Tech Stack:** Laravel 10, PHP 8.x, `Illuminate\Support\Facades\File`, `Illuminate\Support\Facades\Artisan`, PHPUnit via `php artisan test`

---

## File Map

| Action | File | What changes |
|--------|------|--------------|
| Modify | `app/Http/Helpers/helpers.php` | 6 function edits (lines 355–413, 493–497, 1769–1772) |
| Create | `app/Console/Commands/MigrateFilesToStorage.php` | New Artisan command |
| Create | `tests/Unit/Helpers/FileHelperStorageTest.php` | Unit tests for helper functions |
| Create | `tests/Feature/Commands/MigrateFilesToStorageTest.php` | Feature test for Artisan command |

> `app/Console/Kernel.php` does NOT need editing — it already uses `$this->load(__DIR__.'/Commands')` which auto-discovers all commands in that directory.

---

## Task 1: Write failing tests for helper functions

**Files:**
- Create: `tests/Unit/Helpers/FileHelperStorageTest.php`

- [ ] **Step 1: Create the test file**

```php
<?php

namespace Tests\Unit\Helpers;

use Tests\TestCase;

class FileHelperStorageTest extends TestCase
{
    public function test_get_files_path_returns_path_inside_storage(): void
    {
        $path = get_files_path('admin-profile');

        $this->assertStringContainsString(
            'storage' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'public',
            $path
        );
        $this->assertStringContainsString('backend', $path);
        $this->assertStringContainsString('admin', $path);
        $this->assertStringContainsString('profile', $path);
    }

    public function test_get_files_path_does_not_return_public_path(): void
    {
        $path = get_files_path('user-profile');

        // Must NOT point into /public/ directory
        $this->assertStringNotContainsString(
            public_path('frontend/user'),
            $path
        );
    }

    public function test_files_asset_path_returns_storage_url(): void
    {
        $url = files_asset_path('admin-profile');

        $this->assertStringContainsString('storage/backend/images/admin/profile', $url);
        $this->assertStringNotContainsString('public/backend', $url);
    }

    public function test_files_asset_path_car_models_returns_storage_url(): void
    {
        $url = files_asset_path('car-models');

        $this->assertStringContainsString('storage/backend/images/car-models', $url);
    }

    public function test_files_asset_path_basename_returns_storage_prefix(): void
    {
        $basename = files_asset_path_basename('admin-profile');

        $this->assertEquals('storage/backend/images/admin/profile', $basename);
    }

    public function test_files_asset_path_basename_user_profile_returns_storage_prefix(): void
    {
        $basename = files_asset_path_basename('user-profile');

        $this->assertEquals('storage/frontend/user', $basename);
    }

    public function test_get_image_default_returns_storage_url(): void
    {
        $url = get_image(null);

        $this->assertStringContainsString('storage/', $url);
        $this->assertStringNotContainsString('/public/backend', $url);
    }

    public function test_get_image_profile_default_returns_storage_url(): void
    {
        $url = get_image(null, null, 'profile');

        $this->assertStringContainsString('storage/', $url);
        $this->assertStringNotContainsString('/public/backend', $url);
    }
}
```

- [ ] **Step 2: Run the tests to confirm they all fail**

```bash
cd "c:/DATA/Dora Alaseel/Source Code/durra-alaseel-web"
php artisan test tests/Unit/Helpers/FileHelperStorageTest.php --verbose
```

Expected: All 8 tests FAIL. Failures will show paths containing `public/` instead of `storage/`.

---

## Task 2: Fix `get_files_path` and `create_asset_dir`

**Files:**
- Modify: `app/Http/Helpers/helpers.php` (lines 355–369)

These are the filesystem write-path functions. All upload functions call `get_files_path()` to find where to write files.

- [ ] **Step 1: Replace `get_files_path` (line 355–362)**

Find this exact block:
```php
function get_files_path($slug)
{
    $data = files_path($slug);
    $path = $data->path;
    create_asset_dir($path);

    return public_path($path);
}
```

Replace with:
```php
function get_files_path($slug)
{
    $data = files_path($slug);
    $path = $data->path;
    create_asset_dir($path);

    return storage_path('app/public/' . $path);
}
```

- [ ] **Step 2: Replace `create_asset_dir` (lines 364–369)**

Find this exact block:
```php
function create_asset_dir($path)
{
    $path = "public/" . $path;
    if (file_exists($path)) return true;
    return mkdir($path, 0755, true);
}
```

Replace with:
```php
function create_asset_dir($path)
{
    $full_path = storage_path('app/public/') . $path;
    if (file_exists($full_path)) return true;
    return mkdir($full_path, 0755, true);
}
```

- [ ] **Step 3: Run the path tests to verify they pass**

```bash
php artisan test tests/Unit/Helpers/FileHelperStorageTest.php --filter="test_get_files_path" --verbose
```

Expected: Both `test_get_files_path_*` tests PASS.

---

## Task 3: Fix URL generation functions

**Files:**
- Modify: `app/Http/Helpers/helpers.php` (lines 493–497, 371–390, 392–413, 1769–1772)

- [ ] **Step 1: Replace `files_asset_path` (lines 493–497)**

Find:
```php
function files_asset_path($slug)
{
    $files_path = files_path($slug)->path;
    return asset('public/' . $files_path);
}
```

Replace with:
```php
function files_asset_path($slug)
{
    $files_path = files_path($slug)->path;
    return asset('storage/' . $files_path);
}
```

- [ ] **Step 2: Replace `files_asset_path_basename` (lines 1769–1772)**

Find:
```php
function files_asset_path_basename($slug)
{
    return "public/" . files_path($slug)->path;
}
```

Replace with:
```php
function files_asset_path_basename($slug)
{
    return "storage/" . files_path($slug)->path;
}
```

- [ ] **Step 3: Fix inline `asset()` calls in `get_image` (lines 371–390)**

Find the entire `get_image` function:
```php
function get_image($image_name, $path_type = null, $image_type = null, $size = null)
{

    if ($image_type == 'profile') {
        $image =  asset('public/' . files_path('profile-default')->path);
    } else {
        $image =  asset('public/' . files_path('default')->path);
    }
    if ($image_name != null) {
        if ($path_type != null) {
            $image_path = files_path($path_type)->path;
            $image_link = $image_path . "/" . $image_name;
            if (file_exists(public_path($image_link))) {
                $image = asset('public/' . $image_link);
            }
        }
    }

    return $image;
}
```

Replace with:
```php
function get_image($image_name, $path_type = null, $image_type = null, $size = null)
{

    if ($image_type == 'profile') {
        $image = files_asset_path('profile-default');
    } else {
        $image = files_asset_path('default');
    }
    if ($image_name != null) {
        if ($path_type != null) {
            $image_path = files_path($path_type)->path;
            $image_link = $image_path . "/" . $image_name;
            if (file_exists(storage_path('app/public/' . $image_link))) {
                $image = asset('storage/' . $image_link);
            }
        }
    }

    return $image;
}
```

- [ ] **Step 4: Fix inline `asset()` calls in `get_storage_image` (lines 392–413)**

Find the entire `get_storage_image` function:
```php
function get_storage_image($image_name, $path_type = null, $image_type = null, $size = null)
{

    if ($image_type == 'profile') {
        $image =  asset(files_path('profile-default')->path);
    } else {
        $image =  asset(files_path('default')->path);
    }
    if ($image_name != null) {
        if ($path_type != null) {
            $image_path = files_path($path_type)->path;
            $image_link = $image_path . "/" . $image_name;

            if (file_exists(storage_path($image_link))) {
                // if(file_exists(public_path($image_link))) {
                $image = asset($image_link);
            }
        }
    }

    return $image;
}
```

Replace with:
```php
function get_storage_image($image_name, $path_type = null, $image_type = null, $size = null)
{

    if ($image_type == 'profile') {
        $image = files_asset_path('profile-default');
    } else {
        $image = files_asset_path('default');
    }
    if ($image_name != null) {
        if ($path_type != null) {
            $image_path = files_path($path_type)->path;
            $image_link = $image_path . "/" . $image_name;

            if (file_exists(storage_path('app/public/' . $image_link))) {
                $image = asset('storage/' . $image_link);
            }
        }
    }

    return $image;
}
```

- [ ] **Step 5: Run all helper unit tests to confirm they all pass**

```bash
php artisan test tests/Unit/Helpers/FileHelperStorageTest.php --verbose
```

Expected: All 8 tests PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Helpers/helpers.php tests/Unit/Helpers/FileHelperStorageTest.php
git commit -m "refactor: redirect uploaded file paths from /public to storage/app/public"
```

---

## Task 4: Create the `MigrateFilesToStorage` Artisan command

**Files:**
- Create: `app/Console/Commands/MigrateFilesToStorage.php`
- Create: `tests/Feature/Commands/MigrateFilesToStorageTest.php`

- [ ] **Step 1: Write the failing feature test first**

```php
<?php

namespace Tests\Feature\Commands;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MigrateFilesToStorageTest extends TestCase
{
    private string $sourceDir;
    private string $destDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sourceDir = public_path('backend/images/admin/profile');
        $this->destDir   = storage_path('app/public/backend/images/admin/profile');
    }

    protected function tearDown(): void
    {
        // Clean up test files
        if (File::exists($this->sourceDir . '/test-migrate.webp')) {
            File::delete($this->sourceDir . '/test-migrate.webp');
        }
        if (File::exists($this->destDir . '/test-migrate.webp')) {
            File::delete($this->destDir . '/test-migrate.webp');
        }

        parent::tearDown();
    }

    public function test_command_copies_files_from_public_to_storage(): void
    {
        File::makeDirectory($this->sourceDir, 0755, true, true);
        File::put($this->sourceDir . '/test-migrate.webp', 'fake-image-data');

        $this->artisan('files:migrate-to-storage')
            ->assertSuccessful();

        $this->assertFileExists($this->destDir . '/test-migrate.webp');
    }

    public function test_command_preserves_original_files_in_public(): void
    {
        File::makeDirectory($this->sourceDir, 0755, true, true);
        File::put($this->sourceDir . '/test-migrate.webp', 'fake-image-data');

        $this->artisan('files:migrate-to-storage')->assertSuccessful();

        // Original must NOT be deleted
        $this->assertFileExists($this->sourceDir . '/test-migrate.webp');
    }

    public function test_command_is_idempotent_and_skips_existing_files(): void
    {
        File::makeDirectory($this->sourceDir, 0755, true, true);
        File::put($this->sourceDir . '/test-migrate.webp', 'fake-image-data');

        $this->artisan('files:migrate-to-storage')->assertSuccessful();
        $this->artisan('files:migrate-to-storage')
            ->expectsOutputToContain('skipped')
            ->assertSuccessful();
    }
}
```

- [ ] **Step 2: Run the feature test to confirm it fails**

```bash
php artisan test tests/Feature/Commands/MigrateFilesToStorageTest.php --verbose
```

Expected: All 3 tests FAIL with "Command [files:migrate-to-storage] is not defined."

- [ ] **Step 3: Create the command**

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class MigrateFilesToStorage extends Command
{
    protected $signature = 'files:migrate-to-storage';

    protected $description = 'Copy uploaded files from /public to storage/app/public and create the storage symlink';

    /**
     * Upload directory paths from the files_path() registry.
     * Excludes file-path slugs (default, profile-default) and language files.
     */
    private array $uploadPaths = [
        'backend/images/admin/profile',
        'backend/images/car-models',
        'backend/images/currency-flag',
        'backend/images/web-settings/image-assets',
        'backend/images/seo',
        'backend/images/app',
        'backend/images/payment-gateways',
        'backend/images/extensions',
        'backend/images/default',
        'frontend/user',
        'frontend/images/site-section',
        'frontend/images/support-ticket/attachment',
        'backend/files/kyc-files',
        'backend/files/junk-files',
        'frontend/user-national-id',
        'frontend/user-driving-license',
    ];

    public function handle(): int
    {
        $this->info('Creating storage symlink...');
        Artisan::call('storage:link', ['--force' => true]);
        $this->line(trim(Artisan::output()));

        $this->newLine();
        $this->info('Migrating files from /public to storage/app/public...');
        $this->newLine();

        $moved   = 0;
        $skipped = 0;
        $errors  = 0;

        foreach ($this->uploadPaths as $path) {
            $source      = public_path($path);
            $destination = storage_path('app/public/' . $path);

            if (!File::isDirectory($source)) {
                $this->line("  <comment>No source dir:</comment> {$path}");
                continue;
            }

            if (!File::isDirectory($destination)) {
                File::makeDirectory($destination, 0755, true);
            }

            foreach (File::allFiles($source) as $file) {
                $relative = $file->getRelativePathname();
                $destFile = $destination . DIRECTORY_SEPARATOR . $relative;

                if (File::exists($destFile)) {
                    $skipped++;
                    continue;
                }

                $destDir = dirname($destFile);
                if (!File::isDirectory($destDir)) {
                    File::makeDirectory($destDir, 0755, true);
                }

                try {
                    File::copy($file->getRealPath(), $destFile);
                    $moved++;
                } catch (\Exception $e) {
                    $this->error("  Failed: {$relative} — " . $e->getMessage());
                    $errors++;
                }
            }

            $this->line("  <info>Processed:</info> {$path}");
        }

        $this->newLine();
        $this->info("Done: {$moved} moved, {$skipped} skipped, {$errors} errors.");
        $this->newLine();
        $this->warn('Original files in /public have NOT been deleted. Verify everything works, then remove them manually.');

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
```

Save to: `app/Console/Commands/MigrateFilesToStorage.php`

- [ ] **Step 4: Run the feature tests to confirm they pass**

```bash
php artisan test tests/Feature/Commands/MigrateFilesToStorageTest.php --verbose
```

Expected: All 3 tests PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Console/Commands/MigrateFilesToStorage.php tests/Feature/Commands/MigrateFilesToStorageTest.php
git commit -m "feat: add MigrateFilesToStorage Artisan command"
```

---

## Task 5: Run migration and end-to-end verification

- [ ] **Step 1: Run the full test suite to confirm nothing is broken**

```bash
php artisan test --verbose
```

Expected: All tests PASS (no regressions).

- [ ] **Step 2: Run the migration command**

```bash
php artisan files:migrate-to-storage
```

Expected output:
```
Creating storage symlink...
The [public/storage] directory has been linked.

Migrating files from /public to storage/app/public...

  Processed: backend/images/admin/profile
  Processed: backend/images/car-models
  ...

Done: X moved, 0 skipped, 0 errors.

Original files in /public have NOT been deleted. Verify everything works, then remove them manually.
```

- [ ] **Step 3: Verify the symlink exists**

```bash
ls -la "c:/DATA/Dora Alaseel/Source Code/durra-alaseel-web/public/storage"
```

Expected: `public/storage` is a symlink pointing to `../storage/app/public`

- [ ] **Step 4: Verify files landed in storage**

```bash
ls "c:/DATA/Dora Alaseel/Source Code/durra-alaseel-web/storage/app/public/backend/images/"
```

Expected: directories `admin`, `car-models`, `currency-flag`, `default`, `extensions`, `payment-gateways`, `seo`, `web-settings`

- [ ] **Step 5: Start the dev server and manually verify the admin panel**

```bash
php artisan serve --host=192.168.1.211 --port=8001
```

Open the admin panel in a browser. Verify:
- Site logo renders correctly
- Admin profile photo renders correctly
- Car model images render correctly
- Payment gateway logos render correctly
- Any page with a fallback/default image shows the default image

Check that all image `src` attributes contain `/storage/` not `/public/backend/` or `/public/frontend/`.

- [ ] **Step 6: Test a new upload**

Upload a new admin profile photo via the admin panel. Then run:

```bash
ls "c:/DATA/Dora Alaseel/Source Code/durra-alaseel-web/storage/app/public/backend/images/admin/profile/"
```

Expected: The newly uploaded file appears here (NOT in `public/backend/images/admin/profile/`).

- [ ] **Step 7: Test an API image URL**

Make a request to any API endpoint that returns image URLs, e.g. the car listing endpoint. Verify that `image_url` fields in the JSON response contain `.../storage/backend/...` not `.../public/backend/...`.

- [ ] **Step 8: Final commit**

```bash
git add -A
git commit -m "chore: verify storage migration complete"
```

---

## Post-Migration Cleanup (manual, after verification)

Once everything is confirmed working, delete the old upload directories from `/public/`:

```bash
# Run only after verifying all uploads work and all images display correctly
rm -rf public/backend/images/admin
rm -rf public/backend/images/car-models
rm -rf public/backend/images/currency-flag
rm -rf public/backend/images/web-settings
rm -rf public/backend/images/seo
rm -rf public/backend/images/app
rm -rf public/backend/images/payment-gateways
rm -rf public/backend/images/extensions
rm -rf public/backend/images/default
rm -rf public/backend/files/kyc-files
rm -rf public/backend/files/junk-files
rm -rf public/frontend/user
rm -rf public/frontend/user-national-id
rm -rf public/frontend/user-driving-license
rm -rf public/frontend/images/site-section
rm -rf "public/frontend/images/support-ticket/attachment"
```

> **Do NOT delete** `public/backend/` or `public/frontend/` root directories — they contain static theme assets and CSS/JS files that must stay.
