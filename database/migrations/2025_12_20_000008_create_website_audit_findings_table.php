<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_audit_findings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('website_audit_id');
            $table->string('category', 100);
            $table->enum('type', ['issue', 'strength']);
            $table->string('finding');
            $table->text('description')->nullable();
            $table->enum('severity', ['critical', 'high', 'medium', 'low'])->default('medium');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('website_audit_id')->references('id')->on('website_audits')->onDelete('cascade');

            $table->index('website_audit_id');
            $table->index(['category', 'type']);
            $table->index('severity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_audit_findings');
    }
};
