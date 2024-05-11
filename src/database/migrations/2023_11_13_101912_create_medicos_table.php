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
        Schema::create('medicos', function (Blueprint $table) {

            $table->smallIncrements('id');
            $table->string('nombre', 31);
            $table->string('apellidoPaterno', 63);
            $table->string('apellidoMaterno', 63);
            $table->boolean('genero');
            $table->string('imagen', 100);
            $table->string('linkedin', 255)->nullable()->unique();
            $table->string('descripcion', 1000);
            $table->string('CMP', 10)->nullable()->unique();
            $table->string('RNE', 10)->nullable()->unique();
            $table->string('CBP', 10)->nullable()->unique();
            $table->boolean('tipo');
            $table->unsignedTinyInteger('sede_id');
            $table->foreign('sede_id')->references('id')->on('sedes');
            $table->boolean('vigente')->default(true)->comment('0: No Vigente, 1: Vigente');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medicos');
    }
};
