<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_audits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('audit_id')->unique();
            $table->integer('technical_seo_score')->default(0);
            $table->integer('content_quality_score')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('audit_id')->references('id')->on('audits')->onDelete('cascade');
            $table->index('audit_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_audits');
    }
};
