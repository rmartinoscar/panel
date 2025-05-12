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
        Schema::table('eggs', function (Blueprint $table) {
            $table->string('healthcheck')->nullable()->after('startup');
        });
        Schema::table('servers', function (Blueprint $table) {
            $table->string('healthcheck')->default('')->after('startup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('eggs', function (Blueprint $table) {
            $table->dropColumn('healthcheck');
        });
        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn('healthcheck');
        });
    }
};
