<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('activity_logs')) {
            Schema::create('activity_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('action', 100);
                $table->text('description')->nullable();
                $table->string('resource_type', 100)->nullable();
                $table->unsignedBigInteger('resource_id')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 500)->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

                $table->index('user_id');
                $table->index('action');
                $table->index('created_at');
                $table->index(['resource_type', 'resource_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
