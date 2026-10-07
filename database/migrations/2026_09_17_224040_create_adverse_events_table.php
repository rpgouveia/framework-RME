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
        Schema::create('adverse_events', function (Blueprint $table) {
            $table->id();
            $table->text('description');
            $table->date('occurrence_date');
            $table->foreignId('ai_system_id')->constrained('ai_systems');
            $table->timestamps();
        });

        // The MIT risk subdomains (level 2 of mit-ai-risk-domains) the event
        // materializes: one or more, checked on validation.
        Schema::create('adverse_event_risk_subdomains', function (Blueprint $table) {
            $table->foreignId('adverse_event_id')->constrained('adverse_events')->cascadeOnDelete();
            $table->foreignId('risk_subdomain_id')->constrained('taxonomy_terms');
            $table->primary(['adverse_event_id', 'risk_subdomain_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('adverse_event_risk_subdomains');
        Schema::dropIfExists('adverse_events');
    }
};
