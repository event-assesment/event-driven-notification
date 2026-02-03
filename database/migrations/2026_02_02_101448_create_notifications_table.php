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
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('batch_id');
            $table->uuid('template_id')->nullable();
            $table->string('to');
            $table->enum('channel', ['sms', 'email', 'push']);
            $table->text('content')->nullable();
            $table->json('variables')->nullable();
            $table->enum('priority', ['high', 'normal', 'low'])->default('normal');
            $table->enum('status', [
                'pending',
                'queued',
                'sending',
                'accepted',
                'delivered',
                'failed',
                'canceled',
                'scheduled',
                'unknown',
            ])->default('pending');
            $table->string('provider_message_id')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->string('correlation_id', 64)->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('last_status_check_at')->nullable();
            $table->timestamp('next_status_check_at')->nullable();
            $table->timestamps();

            $table->index('batch_id');
            $table->index(['status', 'channel']);
            $table->index('provider_message_id');
            $table->unique(['batch_id', 'to', 'channel'], 'uk_idempotency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
