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
        Schema::create('risks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->string('category');
            $table->string('lifecycle_phase');
            $table->string('uncertainty_level');
            $table->foreignId('ai_system_id')->constrained('ai_systems');
            $table->timestamps();
        });

        // A risk name may recur across AI systems, but only once within one,
        // in any letter case.
        DB::statement('CREATE UNIQUE INDEX risks_ai_system_id_name_unique ON risks (ai_system_id, lower(name))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('risks');
    }
};
