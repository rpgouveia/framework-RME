<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('status_histories', function (Blueprint $table) {
            $table->id();
            // Each entry changes exactly one dimension (0013): progress
            // (previous_status, new_status) or verification
            // (previous_verification, new_verification).
            $table->string('previous_status')->nullable();
            $table->string('new_status')->nullable();
            $table->string('previous_verification')->nullable();
            $table->string('new_verification')->nullable();
            $table->string('trigger_reason')->nullable();
            $table->date('change_date');
            $table->foreignId('link_id')->constrained('links');
            // A person records every manual change; an automatic one (review
            // due, adverse event, system reclassification) has no author.
            $table->foreignId('owner_id')->nullable()->constrained('owners');
            /*
             * What triggered the entry. The CHECK constraints guard the two
             * rules RecordStatusChange enforces, on SQLite and PostgreSQL
             * alike: one dimension per entry, and an owner on every manual
             * entry. A column CHECK may name other columns on both.
             */
            $table->rawColumn('origin', "varchar(255) default 'manual' check ("
                .'(new_status is not null and previous_verification is null and new_verification is null)'
                .' or (new_status is null and previous_status is null and new_verification is not null)'
                .") check (origin <> 'manual' or owner_id is not null)");
            $table->foreignId('adverse_event_id')->nullable()->constrained('adverse_events');
            // The change of the system that caused a reversal (0021).
            $table->foreignId('system_change_id')->nullable()->constrained('system_changes');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('status_histories');
    }
};
