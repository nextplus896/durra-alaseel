<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('balance_transactions', function (Blueprint $table) {
            $table->string('reference_type', 100)->nullable()->after('booking_id')
                ->comment('Polymorphic model class (e.g. CarBooking, PaymentTransaction)');
            $table->unsignedBigInteger('reference_id')->nullable()->after('reference_type')
                ->comment('Polymorphic target ID');
            $table->string('moyasar_invoice_id', 100)->nullable()->unique()->after('status')
                ->comment('Links to payment_transactions.invoice_id');
            $table->string('idempotency_key', 100)->nullable()->unique()->after('moyasar_invoice_id')
                ->comment('Prevents duplicate transactions');

            $table->index(['reference_type', 'reference_id'], 'bt_reference_index');
        });
    }

    public function down(): void
    {
        Schema::table('balance_transactions', function (Blueprint $table) {
            $table->dropIndex('bt_reference_index');
            $table->dropColumn(['reference_type', 'reference_id', 'moyasar_invoice_id', 'idempotency_key']);
        });
    }
};
