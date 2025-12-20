<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('website_url', 500);
            $table->string('business_name');
            $table->string('industry')->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->text('description')->nullable();
            $table->json('keywords')->nullable();
            $table->string('logo_url', 500)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamp('last_audited_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->index('user_id');
            $table->index('website_url');
            $table->index(['user_id', 'website_url']);
            $table->index('created_at');
            $table->unique(['user_id', 'website_url']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};
