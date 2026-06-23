<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Unifica el enum 'estado' de expedientes.
 *
 * Problema: la BD tenía en_proceso|pausado|aprobado|firmado|cerrado|cancelado
 * pero la API validaba en_proceso|documentacion|autorizado|escrituracion|cerrado|cancelado
 * — cuatro valores distintos en cada lado.
 *
 * Nuevo set unificado que refleja el proceso real del cliente (20 pasos):
 *   en_proceso        → documentos recopilándose
 *   documentacion     → documentos completos, enviando a SOFOM
 *   en_catastro       → trámite ante catastro municipal (paso 10)
 *   pre_avaluo        → pre-avalúo solicitado (paso 12-13)
 *   cuv_generada      → CUV generada en RUV, esperando activación (paso 14-15)
 *   avaluo_cerrado    → avalúo cerrado con vigencia 6 meses (paso 17)
 *   en_notaria        → expediente en notaría, esperando CLG y firma (paso 19)
 *   firmado           → firmado ante notario, en Guarda Valores (paso 20)
 *   cerrado           → pago recibido, expediente finalizado
 *   pausado           → en espera por cualquier motivo
 *   cancelado         → expediente cancelado
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') return;

        // Mapear valores legacy a los nuevos antes de cambiar el enum
        DB::statement("
            UPDATE expedientes SET estado = CASE estado
                WHEN 'documentacion'  THEN 'documentacion'
                WHEN 'autorizado'     THEN 'pre_avaluo'
                WHEN 'escrituracion'  THEN 'en_notaria'
                WHEN 'aprobado'       THEN 'pre_avaluo'
                WHEN 'firmado'        THEN 'firmado'
                ELSE estado
            END
        ");

        DB::statement("
            ALTER TABLE expedientes MODIFY COLUMN estado
            ENUM(
                'en_proceso',
                'documentacion',
                'en_catastro',
                'pre_avaluo',
                'cuv_generada',
                'avaluo_cerrado',
                'en_notaria',
                'firmado',
                'cerrado',
                'pausado',
                'cancelado'
            ) NOT NULL DEFAULT 'en_proceso'
        ");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') return;

        // Revertir al enum original de la BD
        DB::statement("
            UPDATE expedientes SET estado = CASE estado
                WHEN 'documentacion'  THEN 'en_proceso'
                WHEN 'en_catastro'    THEN 'en_proceso'
                WHEN 'pre_avaluo'     THEN 'aprobado'
                WHEN 'cuv_generada'   THEN 'aprobado'
                WHEN 'avaluo_cerrado' THEN 'aprobado'
                WHEN 'en_notaria'     THEN 'aprobado'
                WHEN 'firmado'        THEN 'firmado'
                ELSE estado
            END
        ");

        DB::statement("
            ALTER TABLE expedientes MODIFY COLUMN estado
            ENUM('en_proceso','pausado','aprobado','firmado','cerrado','cancelado')
            NOT NULL DEFAULT 'en_proceso'
        ");
    }
};
