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
            $table->string('event_type');
            $table->text('description');
            $table->date('occurrence_date');
            $table->foreignId('ai_system_id')->constrained('ai_systems');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('adverse_events');
    }
};
