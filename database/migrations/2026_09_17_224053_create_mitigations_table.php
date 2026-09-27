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
        Schema::create('mitigations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->string('saeri_category');
            $table->text('suggested_target_risk');
            $table->text('expected_evidence');
            $table->string('suggested_cost');
            $table->string('uncertainty_level');
            $table->string('bibliography_source');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mitigations');
    }
};
