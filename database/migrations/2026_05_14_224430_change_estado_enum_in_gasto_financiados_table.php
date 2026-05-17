<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Migrar valores existentes antes de cambiar el ENUM
        DB::table('gasto_financiados')->where('estado', 'pendiente_cobro')->update(['estado' => 'pendiente']);
        DB::table('gasto_financiados')->where('estado', 'cobrado')->update(['estado' => 'pagado']);

        // MODIFY COLUMN solo aplica en MySQL/MariaDB; SQLite no tiene ENUM nativo
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE gasto_financiados MODIFY COLUMN estado ENUM('pendiente','pagado','cancelado') NOT NULL DEFAULT 'pendiente'");
        }
    }

    public function down(): void
    {
        DB::table('gasto_financiados')->where('estado', 'pendiente')->update(['estado' => 'pendiente_cobro']);
        DB::table('gasto_financiados')->where('estado', 'pagado')->update(['estado' => 'cobrado']);
        DB::table('gasto_financiados')->where('estado', 'cancelado')->update(['estado' => 'pendiente_cobro']);

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE gasto_financiados MODIFY COLUMN estado ENUM('pendiente_cobro','cobrado') NOT NULL DEFAULT 'pendiente_cobro'");
        }
    }
};
