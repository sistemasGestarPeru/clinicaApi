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
            $table->string('Fecha', 10);
            $table->string('Imagen', 500);
            $table->mediumText('Descripcion');
            $table->string('user', 50)->nullable();
            $table->boolean('vigente')->default(true)->comment('0: No Vigente, 1: Vigente');
            $table->timestamps();
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
