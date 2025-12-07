<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tier2_endorsements', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('position', 20);
            $table->integer('moodle_course_id');
            $table->timestamps();

            $table->unique(['position']);
            $table->index('moodle_course_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tier2_endorsements');
    }
};