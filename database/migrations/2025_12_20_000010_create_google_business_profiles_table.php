<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_business_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('audit_id')->unique();
            $table->boolean('detected')->default(false);
            $table->integer('listing_quality_score')->default(0);
            $table->string('nap_consistency')->nullable();
            $table->integer('review_count')->default(0);
            $table->decimal('rating', 2, 1)->default(0);
            $table->boolean('complete_profile')->default(false);
            $table->string('profile_url', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('audit_id')->references('id')->on('audits')->onDelete('cascade');
            $table->index('audit_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_business_profiles');
    }
};
