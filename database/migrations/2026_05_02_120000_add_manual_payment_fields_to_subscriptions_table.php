<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->dateTime('payment_received_at')->nullable()->after('yookassa_payment_id');
            $table->string('payment_receipt_file')->nullable()->after('payment_received_at');
            $table->text('payment_note')->nullable()->after('payment_receipt_file');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->dropColumn([
                'payment_received_at',
                'payment_receipt_file',
                'payment_note',
            ]);
        });
    }
};

