<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_comparisons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('audit_id_1');
            $table->unsignedBigInteger('audit_id_2');
            $table->integer('score_improvement')->default(0);
            $table->json('key_improvements')->nullable();
            $table->json('areas_declined')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('audit_id_1')->references('id')->on('audits')->onDelete('cascade');
            $table->foreign('audit_id_2')->references('id')->on('audits')->onDelete('cascade');

            $table->index('audit_id_1');
            $table->index('audit_id_2');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_comparisons');
    }
};
