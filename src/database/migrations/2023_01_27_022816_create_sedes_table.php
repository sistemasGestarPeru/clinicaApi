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
        Schema::create('sedes', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->string('nombre',50);
            $table->boolean('vigente')->default(true)->comment('0: No Vigente, 1: Vigente');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sedes');
    }
};
