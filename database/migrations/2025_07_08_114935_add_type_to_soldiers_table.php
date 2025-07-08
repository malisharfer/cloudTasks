<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('soldiers', function (Blueprint $table) {
            $table->string('type');
        });

        // DB::table('soldiers')->whereNull('type')->update(['type' => 'Regular']);
    }


    public function down(): void
    {
        Schema::table('soldiers', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};