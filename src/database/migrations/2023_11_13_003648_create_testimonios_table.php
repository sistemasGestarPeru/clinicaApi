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
        Schema::create('testimonios', function (Blueprint $table) {

            $table->smallIncrements('id');
            $table->string('nombre', 31)->nullable()->default('-');
            $table->string('apellidoPaterno', 30)->nullable()->default('-');
            $table->string('apellidoMaterno', 30)->nullable()->default('-');
            $table->string('descripcion', 1000);
            $table->string('imagen', 100);
            $table->unsignedTinyInteger('sede_id');
            $table->foreign('sede_id')->references('id')->on('sedes');
            $table->boolean('vigente')->default(true)->comment('0: No Vigente, 1: Vigente');
            $table->string('user', 50)->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('testimonios');
    }
};
