<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_required_familiarisations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('familiarisation_sector_id')
                ->constrained(indexName: 'course_required_fam_sector_foreign')
                ->onDelete('cascade');
            $table->timestamps();

            $table->unique(['course_id', 'familiarisation_sector_id'], 'course_required_fam_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_required_familiarisations');
    }
};
