<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('endorsement_activities', function (Blueprint $table) {
            $table->id();
            $table->integer('endorsement_id')->unique()->index(); // VatEUD endorsement ID
            $table->integer('vatsim_id')->index();
            $table->string('position', 20);
            $table->float('activity_minutes')->default(0.0);
            $table->timestamp('last_updated')->useCurrent();
            $table->date('removal_date')->nullable();
            $table->boolean('removal_notified')->default(false);
            $table->timestamp('created_at_vateud')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['vatsim_id', 'position']);
            $table->index(['removal_date', 'removal_notified']);
            $table->index('last_updated');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('endorsement_activities');
    }
};