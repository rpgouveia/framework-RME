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
        // A change of the system that triggers reassessment (0021): append
        // only, registered by hand.
        Schema::create('system_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_system_id')->constrained('ai_systems');
            // A new model version or a change in the data.
            $table->string('type');
            $table->text('description');
            $table->date('change_date');
            $table->timestamps();
        });

        // The MIT risk subdomains (level 2) the change affects, if known: they
        // limit the reversal to the links of those risks (0021, item 2).
        Schema::create('system_change_risk_subdomains', function (Blueprint $table) {
            $table->foreignId('system_change_id')->constrained('system_changes')->cascadeOnDelete();
            $table->foreignId('risk_subdomain_id')->constrained('taxonomy_terms');
            $table->primary(['system_change_id', 'risk_subdomain_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_change_risk_subdomains');
        Schema::dropIfExists('system_changes');
    }
};
