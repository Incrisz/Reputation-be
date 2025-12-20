<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('audit_id');
            $table->string('report_type', 50);
            $table->string('file_path', 500)->nullable();
            $table->integer('file_size')->default(0);
            $table->string('share_token', 100)->nullable()->unique();
            $table->timestamp('share_expires_at')->nullable();
            $table->integer('download_count')->default(0);
            $table->integer('shared_with_count')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->foreign('audit_id')->references('id')->on('audits')->onDelete('cascade');

            $table->index('audit_id');
            $table->index('share_token');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_reports');
    }
};
