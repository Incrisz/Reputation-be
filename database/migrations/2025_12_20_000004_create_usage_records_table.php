<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usage_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('subscription_id');
            $table->integer('audit_count')->default(0);
            $table->integer('api_calls_count')->default(0);
            $table->integer('businesses_count')->default(0);
            $table->date('period_start');
            $table->date('period_end');
            $table->date('reset_date');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('subscription_id')->references('id')->on('subscriptions')->onDelete('cascade');

            $table->index(['user_id', 'period_start', 'period_end']);
            $table->index('reset_date');
            $table->unique(['user_id', 'period_start', 'period_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_records');
    }
};
