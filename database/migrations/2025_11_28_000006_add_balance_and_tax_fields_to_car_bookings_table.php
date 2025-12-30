<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('car_bookings', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->nullable()->after('car_id');
            $table->decimal('delivery_fee', 28, 8)->default(0)->after('charges');
            $table->decimal('tax_amount', 28, 8)->default(0)->after('delivery_fee');
            $table->decimal('tax_percentage', 5, 2)->default(0)->after('tax_amount');
            $table->decimal('subtotal', 28, 8)->default(0)->after('tax_percentage');
            $table->decimal('total_amount', 28, 8)->default(0)->after('subtotal');
            $table->boolean('is_delivery')->default(false)->after('total_amount');
            $table->boolean('paid_from_balance')->default(false)->after('is_delivery');

            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('car_bookings', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn([
                'branch_id',
                'delivery_fee',
                'tax_amount',
                'tax_percentage',
                'subtotal',
                'total_amount',
                'is_delivery',
                'paid_from_balance'
            ]);
        });
    }
};
