<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('waiting_list_entries', function (Blueprint $table) {
            $table->boolean('is_interested')->default(true)->after('remarks');
            $table->timestamp('interest_confirmed_at')->nullable()->after('is_interested');
            $table->timestamp('removal_date')->nullable()->after('interest_confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::table('waiting_list_entries', function (Blueprint $table) {
            $table->dropColumn(['is_interested', 'interest_confirmed_at', 'removal_date']);
        });
    }
};
