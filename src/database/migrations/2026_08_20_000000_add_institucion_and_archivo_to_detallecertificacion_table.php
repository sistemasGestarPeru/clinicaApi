<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columnsToAdd = [];

        if (!Schema::hasColumn('detallecertificacion', 'Institucion')) {
            $columnsToAdd['Institucion'] = true;
        }

        if (!Schema::hasColumn('detallecertificacion', 'archivo')) {
            $columnsToAdd['archivo'] = true;
        }

        if ($columnsToAdd) {
            Schema::table('detallecertificacion', function (Blueprint $table) use ($columnsToAdd) {
                if (isset($columnsToAdd['Institucion'])) {
                    $table->string('Institucion', 150)->nullable();
                }

                if (isset($columnsToAdd['archivo'])) {
                    $table->string('archivo', 255)->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('detallecertificacion', function (Blueprint $table) {
            if (Schema::hasColumn('detallecertificacion', 'Institucion')) {
                $table->dropColumn('Institucion');
            }

            if (Schema::hasColumn('detallecertificacion', 'archivo')) {
                $table->dropColumn('archivo');
            }
        });
    }
};