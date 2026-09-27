<?php

namespace Database\Seeders;

use App\Models\Station;
use Illuminate\Database\Seeder;

class StationSeeder extends Seeder
{
    /**
     * Load the representative station catalog for the academic graph.
     */
    public function run(): void
    {
        $stations = [
            'niquia' => 'Niquía',
            'bello' => 'Bello',
            'acevedo' => 'Acevedo',
            'universidad' => 'Universidad',
            'hospital' => 'Hospital',
            'prado' => 'Prado',
            'parque-berrio' => 'Parque Berrío',
            'san-antonio' => 'San Antonio',
            'alpujarra' => 'Alpujarra',
            'industriales' => 'Industriales',
            'poblado' => 'Poblado',
            'aguacatala' => 'Aguacatala',
            'ayura' => 'Ayurá',
            'envigado' => 'Envigado',
            'itagui' => 'Itagüí',
            'cisneros' => 'Cisneros',
            'suramericana' => 'Suramericana',
            'estadio' => 'Estadio',
            'floresta' => 'Floresta',
            'santa-lucia' => 'Santa Lucía',
            'san-javier' => 'San Javier',
        ];

        foreach ($stations as $code => $name) {
            Station::updateOrCreate(['code' => $code], ['name' => $name]);
        }
    }
}
