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
        Schema::create('blogs', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('Titulo', 120);
            $table->date('Fecha');
            $table->string('Imagen', 100);
            $table->mediumText('Descripcion');
            $table->boolean('vigente')->default(true)->comment('0: No Vigente, 1: Vigente');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blogs');
    }
};
