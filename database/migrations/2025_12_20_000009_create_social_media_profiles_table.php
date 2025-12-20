<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_media_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('audit_id');
            $table->string('platform', 50);
            $table->string('url', 500)->nullable();
            $table->boolean('presence_detected')->default(false);
            $table->boolean('linked_from_website')->default(false);
            $table->string('profile_quality_estimate')->nullable();
            $table->integer('followers_estimate')->default(0);
            $table->boolean('verified')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('audit_id')->references('id')->on('audits')->onDelete('cascade');

            $table->index('audit_id');
            $table->index('platform');
            $table->unique(['audit_id', 'platform']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_media_profiles');
    }
};
