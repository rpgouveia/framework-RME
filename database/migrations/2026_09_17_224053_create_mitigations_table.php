<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mitigations', function (Blueprint $table) {
            $table->id();
            // The Portuguese name shown in the app.
            $table->string('name');
            // Traceability to Saeri et al.: the literal name and identifier in
            // their database, and the source document it was extracted from.
            $table->string('source_name');
            $table->string('source_reference')->unique();
            $table->string('source_document');
            // Level 2 of the Saeri taxonomy; the category is its parent.
            $table->foreignId('saeri_subcategory_id')->constrained('taxonomy_terms');
            $table->text('description');
            // The framework's own contribution (C2), which Saeri does not
            // assess, and the source that backs it (RNF03).
            $table->text('suggested_target_risk');
            $table->text('expected_evidence');
            $table->string('suggested_cost');
            $table->string('uncertainty_level');
            $table->text('estimate_source');
            $table->timestamps();
        });

        // The catalogue is picked by name: no two entries may share one, in
        // any letter case.
        DB::statement('CREATE UNIQUE INDEX mitigations_name_unique ON mitigations (lower(name))');

        // The risk subdomains (MIT AI Risk Repository) each mitigation treats:
        // every catalogue entry names at least one.
        Schema::create('mitigation_target_subdomains', function (Blueprint $table) {
            $table->foreignId('mitigation_id')->constrained('mitigations')->cascadeOnDelete();
            $table->foreignId('risk_subdomain_id')->constrained('taxonomy_terms');

            $table->primary(['mitigation_id', 'risk_subdomain_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mitigation_target_subdomains');
        Schema::dropIfExists('mitigations');
    }
};
