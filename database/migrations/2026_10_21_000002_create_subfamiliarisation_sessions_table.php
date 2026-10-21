<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subfamiliarisation_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subfamiliarisation_id')
                ->constrained('subfamiliarisations', indexName: 'subfam_session_subfam_foreign')
                ->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('mentor_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('completed_at');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['subfamiliarisation_id', 'user_id'], 'subfam_session_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subfamiliarisation_sessions');
    }
};
