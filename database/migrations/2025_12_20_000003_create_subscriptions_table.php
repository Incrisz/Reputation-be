<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->unsignedBigInteger('subscription_plan_id');
            $table->enum('status', ['active', 'past_due', 'canceled', 'trial'])->default('active');
            $table->enum('billing_cycle', ['monthly', 'annual'])->default('monthly');
            $table->date('current_period_start');
            $table->date('current_period_end');
            $table->timestamp('trial_ends_at')->nullable();
            $table->string('stripe_customer_id')->unique();
            $table->string('stripe_subscription_id')->nullable()->unique();
            $table->string('stripe_payment_method_id')->nullable();
            $table->decimal('price', 8, 2);
            $table->timestamp('renewal_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('subscription_plan_id')->references('id')->on('subscription_plans');

            $table->index('user_id');
            $table->index('stripe_customer_id');
            $table->index('stripe_subscription_id');
            $table->index('status');
            $table->index('current_period_end');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
