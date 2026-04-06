<?php

namespace Database\Seeders;

use App\Models\Sede;
use App\Models\Warehouse;
use App\Models\WarehouseMaterial;
use App\Models\WarehouseMaterialCategory;
use App\Models\WarehouseStock;
use Illuminate\Database\Seeder;

class WarehouseMaterialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categoryDefinitions = [
            'Protección Personal' => 'Equipos de protección para personal asistencial.',
            'Vías y Catéteres' => 'Materiales para acceso vascular y conexiones.',
            'Dializadores y Líneas' => 'Insumos directos para sesiones de hemodiálisis.',
            'Curación y Antisépticos' => 'Productos para limpieza, desinfección y curaciones.',
            'Laboratorio y Monitoreo' => 'Insumos para toma de muestras y control clínico.',
        ];

        $categories = collect($categoryDefinitions)
            ->mapWithKeys(function (string $description, string $name) {
                $category = WarehouseMaterialCategory::query()->updateOrCreate(
                    ['name' => $name],
                    [
                        'description' => $description,
                        'is_active' => true,
                    ]
                );

                return [$name => $category->id];
            });

        $materials = [
            ['code' => 'MAT-001', 'name' => 'Guantes de nitrilo talla S', 'unit' => 'caja', 'category' => 'Protección Personal'],
            ['code' => 'MAT-002', 'name' => 'Guantes de nitrilo talla M', 'unit' => 'caja', 'category' => 'Protección Personal'],
            ['code' => 'MAT-003', 'name' => 'Guantes de nitrilo talla L', 'unit' => 'caja', 'category' => 'Protección Personal'],
            ['code' => 'MAT-004', 'name' => 'Mascarilla quirúrgica 3 pliegues', 'unit' => 'caja', 'category' => 'Protección Personal'],
            ['code' => 'MAT-005', 'name' => 'Respirador N95', 'unit' => 'unidad', 'category' => 'Protección Personal'],
            ['code' => 'MAT-006', 'name' => 'Mandil descartable manga larga', 'unit' => 'unidad', 'category' => 'Protección Personal'],
            ['code' => 'MAT-007', 'name' => 'Gorro descartable', 'unit' => 'paquete', 'category' => 'Protección Personal'],
            ['code' => 'MAT-008', 'name' => 'Cubrezapatos descartables', 'unit' => 'par', 'category' => 'Protección Personal'],
            ['code' => 'MAT-009', 'name' => 'Lentes de protección', 'unit' => 'unidad', 'category' => 'Protección Personal'],
            ['code' => 'MAT-010', 'name' => 'Careta facial', 'unit' => 'unidad', 'category' => 'Protección Personal'],

            ['code' => 'MAT-011', 'name' => 'Catéter venoso central 12Fr', 'unit' => 'unidad', 'category' => 'Vías y Catéteres'],
            ['code' => 'MAT-012', 'name' => 'Catéter venoso central 14Fr', 'unit' => 'unidad', 'category' => 'Vías y Catéteres'],
            ['code' => 'MAT-013', 'name' => 'Aguja para fístula 15G', 'unit' => 'par', 'category' => 'Vías y Catéteres'],
            ['code' => 'MAT-014', 'name' => 'Aguja para fístula 16G', 'unit' => 'par', 'category' => 'Vías y Catéteres'],
            ['code' => 'MAT-015', 'name' => 'Extensión con llave de 3 vías', 'unit' => 'unidad', 'category' => 'Vías y Catéteres'],
            ['code' => 'MAT-016', 'name' => 'Conector sin aguja', 'unit' => 'unidad', 'category' => 'Vías y Catéteres'],
            ['code' => 'MAT-017', 'name' => 'Tapón para catéter estéril', 'unit' => 'unidad', 'category' => 'Vías y Catéteres'],
            ['code' => 'MAT-018', 'name' => 'Guía metálica para catéter', 'unit' => 'unidad', 'category' => 'Vías y Catéteres'],
            ['code' => 'MAT-019', 'name' => 'Introductor vascular', 'unit' => 'unidad', 'category' => 'Vías y Catéteres'],
            ['code' => 'MAT-020', 'name' => 'Jeringa de 10 ml', 'unit' => 'unidad', 'category' => 'Vías y Catéteres'],

            ['code' => 'MAT-021', 'name' => 'Dializador alto flujo FX80', 'unit' => 'unidad', 'category' => 'Dializadores y Líneas'],
            ['code' => 'MAT-022', 'name' => 'Dializador alto flujo FX100', 'unit' => 'unidad', 'category' => 'Dializadores y Líneas'],
            ['code' => 'MAT-023', 'name' => 'Línea arterial para hemodiálisis', 'unit' => 'unidad', 'category' => 'Dializadores y Líneas'],
            ['code' => 'MAT-024', 'name' => 'Línea venosa para hemodiálisis', 'unit' => 'unidad', 'category' => 'Dializadores y Líneas'],
            ['code' => 'MAT-025', 'name' => 'Filtro ultrafiltro de endotoxinas', 'unit' => 'unidad', 'category' => 'Dializadores y Líneas'],
            ['code' => 'MAT-026', 'name' => 'Bicarbonato en cartucho', 'unit' => 'cartucho', 'category' => 'Dializadores y Líneas'],
            ['code' => 'MAT-027', 'name' => 'Solución ácida concentrada', 'unit' => 'galón', 'category' => 'Dializadores y Líneas'],
            ['code' => 'MAT-028', 'name' => 'Suero fisiológico 0.9% 500 ml', 'unit' => 'bolsa', 'category' => 'Dializadores y Líneas'],
            ['code' => 'MAT-029', 'name' => 'Heparina sódica 5000 UI/ml', 'unit' => 'frasco', 'category' => 'Dializadores y Líneas'],
            ['code' => 'MAT-030', 'name' => 'Clamp para línea de sangre', 'unit' => 'unidad', 'category' => 'Dializadores y Líneas'],

            ['code' => 'MAT-031', 'name' => 'Clorhexidina alcohólica 2%', 'unit' => 'frasco', 'category' => 'Curación y Antisépticos'],
            ['code' => 'MAT-032', 'name' => 'Povidona yodada solución', 'unit' => 'frasco', 'category' => 'Curación y Antisépticos'],
            ['code' => 'MAT-033', 'name' => 'Alcohol etílico 70%', 'unit' => 'frasco', 'category' => 'Curación y Antisépticos'],
            ['code' => 'MAT-034', 'name' => 'Gasas estériles 10x10', 'unit' => 'paquete', 'category' => 'Curación y Antisépticos'],
            ['code' => 'MAT-035', 'name' => 'Apósito transparente 10x12', 'unit' => 'unidad', 'category' => 'Curación y Antisépticos'],
            ['code' => 'MAT-036', 'name' => 'Esparadrapo hipoalergénico', 'unit' => 'rollo', 'category' => 'Curación y Antisépticos'],
            ['code' => 'MAT-037', 'name' => 'Venda elástica 4 pulgadas', 'unit' => 'rollo', 'category' => 'Curación y Antisépticos'],
            ['code' => 'MAT-038', 'name' => 'Torunda de algodón estéril', 'unit' => 'paquete', 'category' => 'Curación y Antisépticos'],
            ['code' => 'MAT-039', 'name' => 'Hisopo estéril', 'unit' => 'unidad', 'category' => 'Curación y Antisépticos'],
            ['code' => 'MAT-040', 'name' => 'Solución salina para curación', 'unit' => 'frasco', 'category' => 'Curación y Antisépticos'],

            ['code' => 'MAT-041', 'name' => 'Tubo al vacío tapa roja', 'unit' => 'unidad', 'category' => 'Laboratorio y Monitoreo'],
            ['code' => 'MAT-042', 'name' => 'Tubo al vacío tapa lila', 'unit' => 'unidad', 'category' => 'Laboratorio y Monitoreo'],
            ['code' => 'MAT-043', 'name' => 'Aguja múltiple para vacutainer', 'unit' => 'unidad', 'category' => 'Laboratorio y Monitoreo'],
            ['code' => 'MAT-044', 'name' => 'Lanceta estéril', 'unit' => 'unidad', 'category' => 'Laboratorio y Monitoreo'],
            ['code' => 'MAT-045', 'name' => 'Tira reactiva de glucosa', 'unit' => 'tira', 'category' => 'Laboratorio y Monitoreo'],
            ['code' => 'MAT-046', 'name' => 'Sensor de oximetría descartable', 'unit' => 'unidad', 'category' => 'Laboratorio y Monitoreo'],
            ['code' => 'MAT-047', 'name' => 'Papel térmico para monitor', 'unit' => 'rollo', 'category' => 'Laboratorio y Monitoreo'],
            ['code' => 'MAT-048', 'name' => 'Jeringa de gasometría', 'unit' => 'unidad', 'category' => 'Laboratorio y Monitoreo'],
            ['code' => 'MAT-049', 'name' => 'Contenedor de muestras biológicas', 'unit' => 'unidad', 'category' => 'Laboratorio y Monitoreo'],
            ['code' => 'MAT-050', 'name' => 'Etiqueta térmica para trazabilidad', 'unit' => 'rollo', 'category' => 'Laboratorio y Monitoreo'],
        ];

        foreach ($materials as $material) {
            WarehouseMaterial::query()->updateOrCreate(
                ['code' => $material['code']],
                [
                    'name' => $material['name'],
                    'unit' => $material['unit'],
                    'warehouse_material_category_id' => $categories->get($material['category']),
                    'is_active' => true,
                ]
            );
        }

        $principalSede = Sede::query()->where('is_principal', true)->first();
        $principalWarehouse = null;

        if ($principalSede) {
            $principalWarehouse = Warehouse::query()->updateOrCreate(
                ['sede_id' => $principalSede->id],
                [
                    'name' => 'Almacén '.$principalSede->name,
                    'is_principal' => true,
                    'is_active' => true,
                ]
            );
        }

        if ($principalWarehouse) {
            WarehouseMaterial::query()->select(['id'])->chunkById(200, function ($materialsChunk) use ($principalWarehouse) {
                foreach ($materialsChunk as $material) {
                    WarehouseStock::query()->updateOrCreate(
                        [
                            'warehouse_id' => $principalWarehouse->id,
                            'warehouse_material_id' => $material->id,
                        ],
                        [
                            'current_qty' => 100,
                            'min_qty' => 20,
                        ]
                    );
                }
            });
        }
    }
}
