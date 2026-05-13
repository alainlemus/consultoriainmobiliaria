<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Migrar valores viejos a nuevos
        DB::table('propiedades')->where('estatus', 'disponible')->update(['estatus' => 'en_venta']);
        DB::table('propiedades')->where('estatus', 'apartada')->update(['estatus' => 'pausada']);
        // 'vendida' se mantiene igual
    }

    public function down(): void
    {
        DB::table('propiedades')->where('estatus', 'en_venta')->update(['estatus' => 'disponible']);
        DB::table('propiedades')->where('estatus', 'pausada')->update(['estatus' => 'apartada']);
    }
};
