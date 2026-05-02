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
        Schema::create('subscription_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('paid_for_month');
            $table->dateTime('payment_received_at');
            $table->decimal('amount', 10, 2);
            $table->string('payment_method')->nullable();
            $table->string('receipt_file')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'paid_for_month']);
            $table->index(['subscription_id', 'payment_received_at']);
        });

        // Мягкий перенос ранее сохраненных "одиночных" чеков из subscriptions в новый журнал оплат.
        $legacyRows = DB::table('subscriptions')
            ->whereNotNull('payment_received_at')
            ->orWhereNotNull('payment_receipt_file')
            ->orWhereNotNull('payment_note')
            ->get([
                'id',
                'user_id',
                'expires_at',
                'price',
                'payment_method',
                'payment_received_at',
                'payment_receipt_file',
                'payment_note',
                'created_at',
                'updated_at',
            ]);

        foreach ($legacyRows as $row) {
            $paidForMonth = $row->payment_received_at
                ? (new \DateTime($row->payment_received_at))->format('Y-m-01')
                : ($row->expires_at ? (new \DateTime($row->expires_at))->format('Y-m-01') : date('Y-m-01'));

            DB::table('subscription_payments')->insert([
                'subscription_id' => $row->id,
                'user_id' => $row->user_id,
                'paid_for_month' => $paidForMonth,
                'payment_received_at' => $row->payment_received_at ?? $row->created_at ?? now(),
                'amount' => $row->price ?? 0,
                'payment_method' => $row->payment_method,
                'receipt_file' => $row->payment_receipt_file,
                'note' => $row->payment_note,
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
    }
};
