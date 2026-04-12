<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_working_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->tinyInteger('day_of_week'); // Carbon: 0=Sunday, 6=Saturday
            $table->time('open_time');
            $table->time('close_time');
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->index(['branch_id', 'day_of_week', 'is_enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_working_hours');
    }
};
