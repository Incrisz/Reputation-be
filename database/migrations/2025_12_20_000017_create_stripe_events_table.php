<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('stripe_events')) {
            Schema::create('stripe_events', function (Blueprint $table) {
                $table->id();
                $table->string('event_id')->unique();
                $table->string('event_type', 100);
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('subscription_id')->nullable();
                $table->json('data');
                $table->boolean('processed')->default(false);
                $table->timestamp('processed_at')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('subscription_id')->references('id')->on('subscriptions')->onDelete('set null');

                $table->index('event_id');
                $table->index('event_type');
                $table->index('processed');
                $table->index('created_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stripe_events');
    }
};
