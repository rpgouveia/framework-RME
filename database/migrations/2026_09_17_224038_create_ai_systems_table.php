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
        Schema::create('ai_systems', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // Descriptive only, such as "Atendimento ao cliente": it takes
            // part in no classification or rule.
            $table->string('application_domain')->nullable();
            $table->string('source_type');
            $table->string('category');
            $table->date('registration_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_systems');
    }
};
