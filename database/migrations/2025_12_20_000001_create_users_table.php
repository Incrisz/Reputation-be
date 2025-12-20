<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Modify existing users table or create if it doesn't exist
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->string('phone')->nullable();
                $table->string('company')->nullable();
                $table->string('industry')->nullable();
                $table->string('location')->nullable();
                $table->string('avatar_url', 500)->nullable();
                $table->timestamp('email_verified_at')->nullable();
                $table->boolean('two_factor_enabled')->default(false);
                $table->string('two_factor_secret')->nullable();
                $table->timestamp('last_login_at')->nullable();
                $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
                $table->rememberToken();
                $table->timestamps();

                $table->index('email');
                $table->index('status');
                $table->index('created_at');
            });
        } else {
            // Add missing columns if they don't exist
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'phone')) {
                    $table->string('phone')->nullable();
                }
                if (!Schema::hasColumn('users', 'company')) {
                    $table->string('company')->nullable();
                }
                if (!Schema::hasColumn('users', 'industry')) {
                    $table->string('industry')->nullable();
                }
                if (!Schema::hasColumn('users', 'location')) {
                    $table->string('location')->nullable();
                }
                if (!Schema::hasColumn('users', 'avatar_url')) {
                    $table->string('avatar_url', 500)->nullable();
                }
                if (!Schema::hasColumn('users', 'two_factor_enabled')) {
                    $table->boolean('two_factor_enabled')->default(false);
                }
                if (!Schema::hasColumn('users', 'two_factor_secret')) {
                    $table->string('two_factor_secret')->nullable();
                }
                if (!Schema::hasColumn('users', 'last_login_at')) {
                    $table->timestamp('last_login_at')->nullable();
                }
                if (!Schema::hasColumn('users', 'status')) {
                    $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
                }
            });
        }
    }

    public function down(): void
    {
        // Don't drop users table on rollback as it's shared
    }
};
