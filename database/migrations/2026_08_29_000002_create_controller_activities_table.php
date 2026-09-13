<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('controller_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('cid');
            $table->string('station_logon', 32);
            $table->unsignedSmallInteger('window_days');
            $table->float('minutes')->default(0);
            $table->timestamp('last_session_at')->nullable();
            $table->timestamp('eligible_since')->nullable();
            $table->json('breakdown')->nullable();
            $table->timestamp('calculated_at');
            $table->timestamps();

            $table->unique(['cid', 'station_logon', 'window_days']);
            $table->index('station_logon');
            $table->index('calculated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('controller_activities');
    }
};
