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
        Schema::create('owners', function (Blueprint $table) {
            $table->id();
            $table->string('organizational_role');
            $table->string('area');
            // Null while active; set when a role that no longer exists is
            // retired, so it stops being offered for new links.
            $table->timestamp('deactivated_at')->nullable();
            $table->timestamps();
        });

        // A role within an area names one owner, in any letter case.
        DB::statement('CREATE UNIQUE INDEX owners_role_area_unique ON owners (lower(organizational_role), lower(area))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('owners');
    }
};
