<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SedesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('sedes')->insert(

            [
                [
                    'nombre' => 'Chiclayo',
                    'vigente' => 1,
                ],
                [
                    'nombre' => 'Trujillo',
                    'vigente' => 1,
                ],
            ],            

        );
    }
}
