<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('commander_id')->nullable();
            $table->timestamps();
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->foreign('commander_id')->references('id')->on('soldiers');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
