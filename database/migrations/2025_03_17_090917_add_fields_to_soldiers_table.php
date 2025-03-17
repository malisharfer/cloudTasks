<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {

        Schema::table('soldiers', function (Blueprint $table) {
            $table->integer('max_alerts')->nullable()->default(0);
            $table->integer('max_in_parallel')->nullable()->default(0);
        });

        DB::table('soldiers')->whereNull('max_alerts')->update(['max_alerts' => 0]);
        DB::table('soldiers')->whereNull('max_in_parallel')->update(['max_in_parallel' => 0]);

    }

    public function down(): void
    {
        Schema::table('soldiers', function (Blueprint $table) {
            $table->dropColumn('max_alerts');
            $table->dropColumn('max_in_parallel');

        });
    }
};
