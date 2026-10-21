<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subfamiliarisations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('primary_sector_id')
                ->constrained('familiarisation_sectors', indexName: 'subfam_primary_sector_foreign')
                ->onDelete('cascade');
            $table->foreignId('secondary_sector_id')
                ->constrained('familiarisation_sectors', indexName: 'subfam_secondary_sector_foreign')
                ->onDelete('cascade');
            $table->json('moodle_course_ids');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subfamiliarisations');
    }
};
