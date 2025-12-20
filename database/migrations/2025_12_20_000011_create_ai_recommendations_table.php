<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_recommendations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('audit_id');
            $table->string('category', 100);
            $table->string('priority', 20);
            $table->text('recommendation');
            $table->string('implementation_effort')->nullable();
            $table->string('impact_level')->nullable();
            $table->integer('tokens_used')->default(0);
            $table->string('model_used')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('audit_id')->references('id')->on('audits')->onDelete('cascade');

            $table->index('audit_id');
            $table->index(['category', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_recommendations');
    }
};
