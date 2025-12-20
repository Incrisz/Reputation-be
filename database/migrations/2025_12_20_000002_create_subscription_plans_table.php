<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug', 50)->unique();
            $table->text('description')->nullable();
            $table->decimal('price_monthly', 8, 2)->default(0);
            $table->decimal('price_annual', 8, 2)->default(0);
            $table->integer('audits_per_month')->default(0); // 0 = unlimited
            $table->integer('businesses_limit')->default(0); // 0 = unlimited
            $table->integer('history_retention_days')->default(0); // 0 = unlimited
            $table->boolean('white_label')->default(false);
            $table->enum('support_level', ['community', 'email', 'priority', '24/7'])->default('community');
            $table->json('features')->nullable();
            $table->string('stripe_price_id_monthly', 255)->nullable();
            $table->string('stripe_price_id_annual', 255)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index('slug');
            $table->index('active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
