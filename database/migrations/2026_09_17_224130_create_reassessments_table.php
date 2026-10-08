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
        // The reassessment of a link after a reversal (0020): append only.
        Schema::create('reassessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('link_id')->constrained('links');
            // The reversal it concludes; at most one reassessment each.
            $table->foreignId('reversal_id')->unique()->constrained('status_histories');
            $table->string('outcome');
            $table->foreignId('owner_id')->constrained('owners');
            $table->text('justification');
            /*
             * The cause analysis (0020, item 6): identified (with the cause
             * and the lifecycle phase it came from), not identified (an
             * explicit choice), or not applicable (a review due or a
             * reclassification has no cause to find). The CHECK keeps the
             * three columns consistent, on SQLite and PostgreSQL alike.
             */
            $table->rawColumn('cause_status', 'varchar(255) check ('
                ."(cause_status = 'identified' and cause is not null and cause_phase is not null)"
                ." or (cause_status in ('not_identified', 'not_applicable') and cause is null and cause_phase is null)"
                .')');
            $table->text('cause')->nullable();
            $table->string('cause_phase')->nullable();
            // What an adjustment changed: a list of {field, before, after}.
            $table->json('changes')->nullable();
            // The verification recorded in the same act, if any.
            $table->foreignId('verification_id')->nullable()->unique()->constrained('status_histories');
            $table->date('reassessment_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reassessments');
    }
};
