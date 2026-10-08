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
            $table->string('lifecycle_phase');
            // Two dimensions (0013): progress of the implementation, and
            // whether evidence proves it (declared or verified). Verification
            // is never set from a form, only through RecordStatusChange.
            $table->string('status');
            $table->string('verification_status')->default('declared');
            $table->string('estimated_cost');
            // The observed cost is not kept here: it comes from the latest
            // evidence that reported one (0018).
            $table->date('creation_date');
            // Null while the link is declared: the periodic review starts at
            // its first verification (0018). Also null for a system in the
            // unacceptable tier, which never operates (0017).
            $table->date('next_review_date')->nullable();
            $table->foreignId('risk_id')->constrained('risks');
            $table->foreignId('mitigation_id')->constrained('mitigations');
            $table->foreignId('owner_id')->constrained('owners');
            $table->timestamps();

            // R-6: a risk and a mitigation are linked at most once.
            $table->unique(['risk_id', 'mitigation_id']);
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
