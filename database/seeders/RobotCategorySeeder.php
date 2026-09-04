<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RobotCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'robot_alimentos' => ['Manzana', 'Pan', 'Leche', 'Arroz', 'Carne'],
            'robot_colores' => ['Rojo', 'Azul', 'Amarillo', 'Verde', 'Negro'],
            'robot_casa_items' => ['Silla', 'Mesa', 'Cama', 'Puerta', 'Ventana'],
            'robot_naturaleza_items' => ['Árbol', 'Río', 'Montaña', 'Flor', 'Sol'],
            'robot_familia_items' => ['Madre', 'Padre', 'Hermano', 'Abuela', 'Tío'],
            'robot_herramientas_items' => ['Martillo', 'Destornillador', 'Llave', 'Taladro', 'Alicate'],
            'robot_lugares_items' => ['Escuela', 'Parque', 'Mercado', 'Iglesia', 'Plaza'],
        ];

        foreach ($categories as $table => $items) {
            DB::table($table)->delete();
            foreach ($items as $item) {
                DB::table($table)->insert(['name' => $item, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }
}
