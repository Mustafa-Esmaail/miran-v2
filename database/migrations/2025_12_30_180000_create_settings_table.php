<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();          // site.title, payments.enable
            $table->string('group')->index();         // site, seo, payments, contact
            $table->string('type');                   // string, boolean, json, image
            $table->longText('value')->nullable();    // actual value (json/string/bool)
            $table->boolean('is_translatable')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
