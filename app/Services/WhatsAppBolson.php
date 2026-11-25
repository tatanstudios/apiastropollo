<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Bolson (saldo global) de mensajes de WhatsApp.
 *
 * Cada aviso de orden nueva gasta 1. Flujo del envio:
 *   reservar()  -> descuenta 1 solo si hay saldo (atomico, sirve con varias ordenes a la vez)
 *   enviar a Meta
 *   confirmarEnvio() si salio, devolver() si no salio (no se cobra un mensaje fallido)
 */
class WhatsAppBolson
{
    public const FILA = 1;

    public static function disponibles(): int
    {
        return (int) DB::table('whatsapp_bolson')->where('id', self::FILA)->value('disponibles');
    }

    /** Toma 1 mensaje del bolson. false = saldo en cero, no se debe enviar. */
    public static function reservar(): bool
    {
        return DB::table('whatsapp_bolson')
            ->where('id', self::FILA)
            ->where('disponibles', '>', 0)
            ->decrement('disponibles') > 0;
    }

    /** Regresa el mensaje reservado cuando el envio no salio. */
    public static function devolver(): void
    {
        DB::table('whatsapp_bolson')->where('id', self::FILA)->increment('disponibles');
    }

    /** El mensaje reservado si salio: cuenta el enviado y lo deja en el historial. */
    public static function confirmarEnvio(int $idOrden, ?int $idServicio): void
    {
        DB::transaction(function () use ($idOrden, $idServicio) {
            DB::table('whatsapp_bolson')->where('id', self::FILA)->increment('enviados');
            $saldo = self::disponibles();

            DB::table('whatsapp_movimientos')->insert([
                'tipo' => 'envio',
                'cantidad' => 1,
                'saldo_anterior' => $saldo + 1,
                'saldo_nuevo' => $saldo,
                'id_servicio' => $idServicio,
                'id_orden' => $idOrden,
                'fecha' => now(),
            ]);
        });
    }

    /**
     * Agrega o quita mensajes desde el panel.
     * @param string $tipo 'agregar' | 'quitar'
     * @return int|null el saldo nuevo, o null si se quiere quitar mas de lo que hay
     */
    public static function ajustar(string $tipo, int $cantidad, int $idAdministrador, ?string $nota): ?int
    {
        return DB::transaction(function () use ($tipo, $cantidad, $idAdministrador, $nota) {
            $anterior = (int) DB::table('whatsapp_bolson')->where('id', self::FILA)->lockForUpdate()->value('disponibles');

            if ($tipo === 'quitar' && $cantidad > $anterior) {
                return null;
            }

            $nuevo = $tipo === 'agregar' ? $anterior + $cantidad : $anterior - $cantidad;

            DB::table('whatsapp_bolson')->where('id', self::FILA)->update(['disponibles' => $nuevo]);

            DB::table('whatsapp_movimientos')->insert([
                'tipo' => $tipo,
                'cantidad' => $cantidad,
                'saldo_anterior' => $anterior,
                'saldo_nuevo' => $nuevo,
                'id_administrador' => $idAdministrador,
                'nota' => filled($nota) ? mb_substr(trim($nota), 0, 150) : null,
                'fecha' => now(),
            ]);

            return $nuevo;
        });
    }
}
