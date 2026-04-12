<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add tax columns to basic_settings
        Schema::table('basic_settings', function (Blueprint $table) {
            $table->string('tax_name', 100)->default('VAT')->after('admin_prefix');
            $table->decimal('tax_percentage', 5, 2)->default(15.00)->after('tax_name');
            $table->boolean('tax_status')->default(true)->after('tax_percentage');
            $table->unsignedBigInteger('tax_last_edit_by')->nullable()->after('tax_status');
        });

        // 2. Copy existing tax_settings data into basic_settings (first row only)
        if (Schema::hasTable('tax_settings')) {
            $taxRow = DB::table('tax_settings')->first();
            if ($taxRow) {
                DB::table('basic_settings')->where('id', DB::table('basic_settings')->min('id'))->update([
                    'tax_name'         => $taxRow->name,
                    'tax_percentage'   => $taxRow->percentage,
                    'tax_status'       => $taxRow->status,
                    'tax_last_edit_by' => $taxRow->last_edit_by,
                ]);
            }

            // 3. Drop tax_settings table
            Schema::dropIfExists('tax_settings');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Re-create tax_settings table
        Schema::create('tax_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('VAT');
            $table->decimal('percentage', 5, 2)->default(15.00);
            $table->boolean('status')->default(true);
            $table->unsignedBigInteger('last_edit_by')->nullable();
            $table->foreign('last_edit_by')->references('id')->on('admins')->onDelete('set null');
            $table->timestamps();
        });

        // 2. Copy data back from basic_settings
        $basicRow = DB::table('basic_settings')->first();
        if ($basicRow) {
            DB::table('tax_settings')->insert([
                'name'         => $basicRow->tax_name ?? 'VAT',
                'percentage'   => $basicRow->tax_percentage ?? 15.00,
                'status'       => $basicRow->tax_status ?? true,
                'last_edit_by' => $basicRow->tax_last_edit_by,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }

        // 3. Remove tax columns from basic_settings
        Schema::table('basic_settings', function (Blueprint $table) {
            $table->dropColumn(['tax_name', 'tax_percentage', 'tax_status', 'tax_last_edit_by']);
        });
    }
};
