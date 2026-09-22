<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A freshly created roster entry (new member, or a member re-added
        // after a previous removal) fell back to this DB default instead of
        // NULL. Since a Carbon instance for 1970-01-01 is truthy, the "no
        // known session yet" guard in CheckUserRosterStatus never caught it
        // — so a single failed activity fetch made a brand-new entry look
        // ~56 years inactive and could trigger a bogus warning/removal.
        // Clear any rows still stuck on the sentinel value. The column must
        // be made nullable first, since it is still NOT NULL at this point.
        Schema::table('roster_entries', function (Blueprint $table) {
            $table->dateTime('last_session')->nullable()->change();
        });

        DB::table('roster_entries')
            ->where('last_session', '1970-01-01 00:00:00')
            ->update(['last_session' => null]);
    }

    public function down(): void
    {
        DB::table('roster_entries')
            ->whereNull('last_session')
            ->update(['last_session' => '1970-01-01 00:00:00']);

        Schema::table('roster_entries', function (Blueprint $table) {
            $table->dateTime('last_session')->default('1970-01-01 00:00:00')->change();
        });
    }
};
