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
        Schema::create('portadas', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->string('imagenEsc', 100);
            $table->string('imagenCel', 100);
            $table->string('TextoBtn', 50)->nullable();
            $table->string('UrlBtn', 50)->nullable();
            $table->tinyInteger('identificadorPadre');
            $table->tinyInteger('identificadorHijo');
            $table->boolean('vigente')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portadas');
    }
};
