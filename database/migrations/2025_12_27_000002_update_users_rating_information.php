<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('last_known_rating')->nullable()->after('rating');
            $table->timestamp('rating_upgraded_at')->nullable()->after('last_rating_change');
        });

        DB::table('users')->update(['last_known_rating' => DB::raw('rating')]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['last_known_rating', 'rating_upgraded_at']);
        });
    }
};
