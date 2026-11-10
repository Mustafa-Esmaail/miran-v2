<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSectionFieldsTable extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('section_fields')) {
            Schema::create('section_fields', function (Blueprint $table) {
                $table->id();
                $table->foreignId('section_id')->constrained()->cascadeOnDelete();
                $table->string('key');
                $table->string('type')->default('json');
                $table->json('value')->nullable();
                $table->string('group')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index(['section_id', 'key']);
                $table->index(['section_id', 'sort_order']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('section_fields');
    }
}
