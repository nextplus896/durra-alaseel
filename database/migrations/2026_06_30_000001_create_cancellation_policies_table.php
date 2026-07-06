<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Global single-record cancellation policy table.
     * Pattern mirrors tax_settings: one record managed by the admin via CancellationPolicy::first().
     */
    public function up(): void
    {
        Schema::create('cancellation_policies', function (Blueprint $table) {
            $table->id();

            // How many hours before pickup the "free cancellation" window closes
            $table->unsignedInteger('cancellation_window_hours')->default(4)
                ->comment('Cancel >= this many hours before pickup → only service fee applies');

            // Rental deduction applied when cancellation is inside the window
            // Types: none | fixed | percentage | day
            $table->string('deduction_type', 20)->default('day')
                ->comment('none|fixed|percentage|day');
            $table->decimal('deduction_value', 10, 2)->default(1.00)
                ->comment('Amount/percentage/days to deduct from rental');

            // Service fee always applied regardless of window
            // Types: none | fixed | percentage
            $table->string('service_fee_type', 20)->default('percentage')
                ->comment('none|fixed|percentage');
            $table->decimal('service_fee_value', 10, 2)->default(10.00)
                ->comment('Amount or percentage of service fee');

            $table->boolean('is_active')->default(true);

            // Audit trail — which admin last modified the policy
            $table->unsignedBigInteger('last_edit_by')->nullable();
            $table->foreign('last_edit_by')
                ->references('id')
                ->on('admins')
                ->onDelete('set null');

            $table->timestamps();
        });

        // Seed the default global policy record
        DB::table('cancellation_policies')->insert([
            'cancellation_window_hours' => 4,
            'deduction_type'            => 'day',
            'deduction_value'           => 1.00,
            'service_fee_type'          => 'percentage',
            'service_fee_value'         => 10.00,
            'is_active'                 => true,
            'created_at'                => now(),
            'updated_at'                => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cancellation_policies');
    }
};
