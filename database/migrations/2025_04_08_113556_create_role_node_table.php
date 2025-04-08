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
        Schema::create('role_node', function (Blueprint $table) {
            $table->unsignedInteger('node_id');
            $table->unsignedInteger('role_id');

            $table->unique(['node_id', 'role_id']);

            $table->foreign('node_id')->references('id')->on('nodes')->cascadeOnDelete();
            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_node');
    }
};
