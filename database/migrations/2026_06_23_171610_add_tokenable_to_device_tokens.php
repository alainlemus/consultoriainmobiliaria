<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('device_tokens', function (Blueprint $table) {
            // Agrega soporte polimórfico para Acreditado además de User
            $table->string('tokenable_type')->default('App\\Models\\User')->after('user_id');
            $table->unsignedBigInteger('tokenable_id')->nullable()->after('tokenable_type');
        });

        // Migrar datos existentes: copiar user_id a tokenable_id
        DB::table('device_tokens')->update([
            'tokenable_type' => 'App\\Models\\User',
            'tokenable_id'   => DB::raw('user_id'),
        ]);
    }

    public function down(): void
    {
        Schema::table('device_tokens', function (Blueprint $table) {
            $table->dropColumn(['tokenable_type', 'tokenable_id']);
        });
    }
};
