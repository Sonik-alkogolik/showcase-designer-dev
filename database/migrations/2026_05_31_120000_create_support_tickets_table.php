<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_email')->nullable();
            $table->string('category', 64);
            $table->string('preset', 160)->nullable();
            $table->string('subject');
            $table->text('message');
            $table->string('status', 32)->default('open')->index();
            $table->string('current_url')->nullable();
            $table->string('browser')->nullable();
            $table->timestamp('reported_at')->nullable();
            $table->string('screenshot_path')->nullable();
            $table->text('admin_response')->nullable();
            $table->timestamp('last_admin_replied_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
