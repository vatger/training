<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('gdpr_deleted_at')->nullable()->after('rating_upgrade_pending');
        });

        // Personal identifiers must be erasable independently of the row itself,
        // since related records (training logs, cpts, waiting list entries, ...)
        // keep referencing the user by id and must never be cascade-deleted.
        Schema::table('users', function (Blueprint $table) {
            $table->integer('vatsim_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('vatsim_id')->nullable(false)->change();
            $table->dropColumn('gdpr_deleted_at');
        });
    }
};
