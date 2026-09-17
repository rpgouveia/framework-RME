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
        Schema::create('links', function (Blueprint $table) {
            $table->id();
            $table->string("lifecycle_phase");
            $table->string("status");
            $table->float("estimated_cost");
            $table->float("observed_cost")->nullable();
            $table->date("creation_date");
            $table->date("next_review_date");
            $table->foreignId('risk_id')->constrained('risks');
            $table->foreignId('mitigation_id')->constrained('mitigations');
            $table->foreignId('owner_id')->constrained('owners');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('links');
    }
};
