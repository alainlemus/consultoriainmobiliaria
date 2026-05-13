<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            // 'borrador' | 'programado' | 'publicado'
            $table->string('estado')->default('borrador')->after('publicado');
        });

        // Migrar datos existentes al nuevo campo
        DB::table('posts')->where('publicado', true)->update(['estado' => 'publicado']);
        DB::table('posts')->where('publicado', false)
            ->whereNotNull('published_at')
            ->where('published_at', '>', now())
            ->update(['estado' => 'programado']);
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('estado');
        });
    }
};
