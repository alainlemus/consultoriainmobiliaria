<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contactos', function (Blueprint $table) {
            $table->text('mensaje')->nullable()->change();
            $table->string('servicio')->nullable()->change();
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('contactos', function (Blueprint $table) {
            $table->text('mensaje')->nullable(false)->change();
        });
    }
};
