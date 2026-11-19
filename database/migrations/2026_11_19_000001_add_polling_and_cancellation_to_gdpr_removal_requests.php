<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gdpr_removal_requests', function (Blueprint $table) {
            $table->timestamp('last_polled_at')->nullable()->after('search_terms');
            $table->timestamp('cancelled_at')->nullable()->after('completed_at');
        });

        // Requests that are already open start their 7 days now, not at their creation.
        DB::table('gdpr_removal_requests')
            ->whereIn('status', ['pending', 'approved'])
            ->update(['last_polled_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('gdpr_removal_requests', function (Blueprint $table) {
            $table->dropColumn(['last_polled_at', 'cancelled_at']);
        });
    }
};
