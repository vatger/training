<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('key', 64)->unique();
            $table->json('permissions')->default('[]');
            $table->timestamp('last_used_at')->nullable();
            $table->string('last_used_ip')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('key');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};