# Storage Migration Design
**Date:** 2026-04-13  
**Status:** Approved

## Context

All user-uploaded files (profile photos, car images, KYC documents, logos, payment gateway images, site settings images) are currently stored directly inside `/public/` subdirectories. This is non-standard for Laravel and has two problems:

1. Sensitive files (KYC documents, national IDs, driving licenses) are publicly accessible by direct URL with no access control possible.
2. Laravel's storage abstraction (`Storage` facade, disk swapping, cloud migration) cannot be used while files live in `/public/`.

The goal is to move all uploaded dynamic files to `storage/app/public/` and serve them via Laravel's `public/storage` symlink, while leaving static theme assets (CSS, JS, vendor libraries, frontend UI images) in `/public/` untouched.

## Scope

**In scope — uploaded/dynamic files:**
- Admin profile photos (`backend/images/admin/profile`)
- Car model images (`backend/images/car-models`)
- Currency flags (`backend/images/currency-flag`)
- Site settings images & logos (`backend/images/web-settings/image-assets`)
- SEO images (`backend/images/seo`)
- App onboarding screens (`backend/images/app`)
- Payment gateway logos (`backend/images/payment-gateways`)
- Extension images (`backend/images/extensions`)
- User/vendor profile photos (`frontend/user`)
- Site section images (`frontend/images/site-section`)
- Support ticket attachments (`frontend/images/support-ticket/attachment`)
- KYC files (`backend/files/kyc-files`)
- Junk/temporary files (`backend/files/junk-files`)
- User national ID files (`frontend/user-national-id`)
- User driving license files (`frontend/user-driving-license`)
- Default/fallback images (`backend/images/default`)

**Out of scope — stays in `/public/`:**
- CSS, JS, fonts, vendor libraries
- Static frontend theme images (banners, icons, UI elements)
- Fileholder library temp images (`/public/fileholder/img/`)
- Language files (`backend/files/language`)

## Approach: Helper-Centralized Refactor

The project's file system is fully centralized through 4 helper functions in `app/Http/Helpers/helpers.php`. All 30+ controllers and 6+ models call these functions — none reference `public_path()` directly for uploads. Changing these 4 functions is sufficient to redirect all file I/O.

No controllers, models, Blade views, or API resources need to change.

## Changes

### 1. `helpers.php` — 4 function changes

**`get_files_path($slug)`** — filesystem write path  
```php
// Before
return public_path($path);

// After
return storage_path('app/public/' . $path);
```

**`create_asset_dir($path)`** — directory creation  
```php
// Before
$path = "public/" . $path;
if (file_exists($path)) return true;
return mkdir($path, 0755, true);

// After
$full_path = storage_path('app/public/') . $path;
if (file_exists($full_path)) return true;
return mkdir($full_path, 0755, true);
```

**`files_asset_path($slug)`** — public URL generation  
```php
// Before
return asset('public/' . $files_path);

// After
return asset('storage/' . $files_path);
```

**`files_asset_path_basename($slug)`** — relative path for API responses  
```php
// Before
return "public/" . files_path($slug)->path;

// After
return "storage/" . files_path($slug)->path;
```

### 2. `helpers.php` — 2 inline fixes

`get_image()` and `get_storage_image()` have hardcoded `asset('public/' . ...)` calls for default/fallback images that bypass `files_asset_path()`. Replace `'public/'` with `'storage/'` in both functions.

### 3. New Artisan Command: `MigrateFilesToStorage`

**File:** `app/Console/Commands/MigrateFilesToStorage.php`  
**Command:** `php artisan files:migrate-to-storage`  
**Register in:** `app/Console/Kernel.php`

**Behaviour:**
1. Run `php artisan storage:link` to create the `public/storage → storage/app/public` symlink
2. For each slug in `files_path()`, copy the directory tree from `public/{path}` → `storage/app/public/{path}`
3. Skip files already present in the destination (idempotent — safe to re-run)
4. Leave original files in `/public/` as backup (no deletes — admin cleans up manually after verifying)
5. Output a summary: `X files moved, Y skipped, Z errors`

## Critical Files

| File | Change |
|------|--------|
| `app/Http/Helpers/helpers.php` | Modify `get_files_path`, `create_asset_dir`, `files_asset_path`, `files_asset_path_basename`, `get_image`, `get_storage_image` |
| `app/Console/Commands/MigrateFilesToStorage.php` | Create new file |
| `app/Console/Kernel.php` | Register new command |

## Prerequisites

- `php artisan storage:link` must be run (the migration command does this automatically)
- `storage/app/public/` directory must be writable

## Verification

1. **Run migration:** `php artisan files:migrate-to-storage` — confirm summary output shows files moved
2. **Check symlink:** `ls -la public/storage` → should point to `../storage/app/public`
3. **Admin panel:** Load the admin dashboard — logo, profile images, car model images display correctly
4. **New upload:** Upload a profile photo — confirm file appears in `storage/app/public/backend/images/admin/profile/` not in `public/`
5. **API responses:** Image URLs in JSON should be `.../storage/backend/...` not `.../public/backend/...`
6. **KYC upload:** Upload a KYC document — confirm it lands in `storage/app/public/backend/files/kyc-files/`
7. **Default images:** Pages with no uploaded image should still show default fallback images
