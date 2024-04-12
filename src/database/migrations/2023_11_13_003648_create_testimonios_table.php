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
            $table->id();
            $table->string('nombre', 31)->nullable();
            $table->string('apellidoPaterno', 30)->nullable();
            $table->string('apellidoMaterno', 30)->nullable();
            $table->string('descripcion', 255);
            $table->string('imagen', 255);
            $table->unsignedBigInteger('sede_id'); 
            $table->foreign('sede_id')->references('id')->on('sedes');
            $table->boolean('vigente')->default(true)->comment('0: No Vigente, 1: Vigente');

            
            $table->timestamps();
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
