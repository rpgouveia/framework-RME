<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Reference taxonomies (RNF05) are data, loaded from versioned files and
     * read only in the app. A term points at its parent (an adjacency list):
     * the trees are shallow and only change when a file is reloaded, so a
     * plain parent link is enough. The code ("1.2") is the stable identity
     * the data files refer to; the level is kept to check the tree is
     * coherent and to pick a level without walking it.
     */
    public function up(): void
    {
        Schema::create('taxonomies', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('citation');
            $table->string('version');
            $table->string('url');
            $table->date('accessed_at');
            // Whatever else the source carries, such as its documents.
            $table->json('metadata');
            $table->timestamps();
        });

        Schema::create('taxonomy_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('taxonomy_id')->constrained('taxonomies')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('taxonomy_terms');
            $table->string('code');
            $table->unsignedTinyInteger('level');
            $table->string('name');
            $table->string('original_name');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('position');
            $table->timestamps();

            $table->unique(['taxonomy_id', 'code']);
        });

        // Metadata of other versioned reference files, such as whether the
        // mitigation catalogue in the database is still fictional.
        Schema::create('reference_datasets', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('metadata');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reference_datasets');
        Schema::dropIfExists('taxonomy_terms');
        Schema::dropIfExists('taxonomies');
    }
};
