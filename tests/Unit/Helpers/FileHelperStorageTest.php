<?php

namespace Tests\Unit\Helpers;

use Tests\TestCase;

class FileHelperStorageTest extends TestCase
{
    // -----------------------------------------------------------------------
    // get_files_path()
    // -----------------------------------------------------------------------

    /** @test */
    public function get_files_path_returns_storage_app_public_path(): void
    {
        $result = get_files_path('admin-profile');
        $expected = storage_path('app/public/backend/images/admin/profile');
        $this->assertEquals($expected, $result);
    }

    /** @test */
    public function get_files_path_does_not_return_public_path(): void
    {
        $result = get_files_path('admin-profile');
        $this->assertStringNotContainsString(public_path(), $result);
    }

    // -----------------------------------------------------------------------
    // files_asset_path()
    // -----------------------------------------------------------------------

    /** @test */
    public function files_asset_path_returns_storage_url(): void
    {
        $result = files_asset_path('admin-profile');
        $this->assertStringContainsString('/storage/backend/images/admin/profile', $result);
    }

    /** @test */
    public function files_asset_path_does_not_contain_public_segment(): void
    {
        $result = files_asset_path('admin-profile');
        // URL must not contain the /public/ path segment (only /storage/)
        $this->assertStringNotContainsString('/public/backend', $result);
    }

    /** @test */
    public function files_asset_path_for_user_profile_returns_storage_url(): void
    {
        $result = files_asset_path('user-profile');
        $this->assertStringContainsString('/storage/frontend/user', $result);
    }

    // -----------------------------------------------------------------------
    // files_asset_path_basename()
    // -----------------------------------------------------------------------

    /** @test */
    public function files_asset_path_basename_returns_storage_prefix(): void
    {
        $result = files_asset_path_basename('admin-profile');
        $this->assertStringStartsWith('storage/', $result);
    }

    /** @test */
    public function files_asset_path_basename_does_not_start_with_public(): void
    {
        $result = files_asset_path_basename('admin-profile');
        $this->assertStringStartsNotWith('public/', $result);
    }

    // -----------------------------------------------------------------------
    // get_files_path() — car-models slug
    // -----------------------------------------------------------------------

    /** @test */
    public function get_files_path_for_car_models_returns_correct_storage_path(): void
    {
        $result = get_files_path('car-models');
        $expected = storage_path('app/public/backend/images/car-models');
        $this->assertEquals($expected, $result);
    }
}
