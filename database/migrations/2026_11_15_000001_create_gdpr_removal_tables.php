<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gdpr_removal_requests', function (Blueprint $table) {
            $table->id();
            // Deliberately not a foreign key: the user row is deleted on approval,
            // the request has to outlive it to keep answering the hourly polls.
            $table->unsignedBigInteger('user_id')->nullable();
            $table->integer('vatsim_id')->index();
            // Identifiers the reviewers search for in free text. Wiped on completion.
            $table->string('user_name')->nullable();
            $table->json('search_terms')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('gdpr_review_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gdpr_removal_request_id')->constrained()->cascadeOnDelete();
            $table->string('table_name', 64);
            $table->unsignedBigInteger('record_id');
            $table->string('label');
            $table->string('reason', 20); // 'author' = user was detached from it, 'mention' = text matches an identifier
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['gdpr_removal_request_id', 'table_name', 'record_id'], 'gdpr_review_item_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gdpr_review_items');
        Schema::dropIfExists('gdpr_removal_requests');
    }
};
