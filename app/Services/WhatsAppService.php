<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Envio de mensajes por WhatsApp Cloud API (Meta).
 *
 * Los mensajes que inicia el negocio deben usar una PLANTILLA aprobada en Meta
 * (WhatsApp Manager > Plantillas de mensajes), categoria "Utilidad". Plantilla esperada:
 *   nombre:  nueva_orden   (WHATSAPP_PLANTILLA_ORDEN)
 *   idioma:  es            (WHATSAPP_IDIOMA)
 *   cuerpo:  El pedido número #{{1}} ha...  (aprobada 22/11/2026; un solo parametro: id de la orden)
 *
 * Configuracion en config/services.php (seccion whatsapp) a partir del .env.
 */
class WhatsAppService
{
    /** WhatsApp configurado (token y numero emisor). Sin esto no se intenta enviar. */
    public static function configurado(): bool
    {
        return filled(config('services.whatsapp.token')) && filled(config('services.whatsapp.phone_number_id'));
    }

    /**
     * Deja el numero como lo pide Meta: solo digitos con codigo de pais (sin "+").
     * Un numero local de 8 digitos se completa con el codigo de pais por defecto (503).
     * @return string|null null si no parece un numero valido
     */
    public static function normalizarNumero(?string $numero): ?string
    {
        $digitos = preg_replace('/\D+/', '', (string) $numero);
        if ($digitos === '') {
            return null;
        }
        if (strlen($digitos) === 8) {
            $digitos = config('services.whatsapp.codigo_pais', '503') . $digitos;
        }
        return (strlen($digitos) >= 10 && strlen($digitos) <= 15) ? $digitos : null;
    }

    /** Aviso de orden nueva al restaurante. */
    public static function enviarNuevaOrden(string $numero, int $idOrden): string
    {
        return self::enviarPlantilla(
            $numero,
            config('services.whatsapp.plantilla_orden'),
            [(string) $idOrden]
        );
    }

    /**
     * Envia una plantilla con parametros de texto en el cuerpo.
     * @return string 'enviado' | 'no_configurado' | 'numero_invalido' | 'rechazado' | 'error'
     *   'error' = fallo temporal (red, 5xx, limite); vale la pena reintentar.
     *   'rechazado' = Meta rechazo el envio (plantilla, numero, token); reintentar no sirve.
     */
    public static function enviarPlantilla(string $numero, string $plantilla, array $parametros = []): string
    {
        if (!self::configurado()) {
            return 'no_configurado';
        }

        $numero = self::normalizarNumero($numero);
        if ($numero === null) {
            return 'numero_invalido';
        }

        $config = config('services.whatsapp');
        $url = 'https://graph.facebook.com/' . $config['version'] . '/' . $config['phone_number_id'] . '/messages';

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $numero,
            'type' => 'template',
            'template' => [
                'name' => $plantilla,
                'language' => ['code' => $config['idioma']],
            ],
        ];
        if (!empty($parametros)) {
            $payload['template']['components'] = [[
                'type' => 'body',
                'parameters' => array_map(fn ($p) => ['type' => 'text', 'text' => (string) $p], $parametros),
            ]];
        }

        try {
            $respuesta = Http::acceptJson()
                ->withToken($config['token'])
                ->timeout(10)
                ->post($url, $payload);

            if ($respuesta->successful() && !empty($respuesta->json('messages.0.id'))) {
                return 'enviado';
            }

            Log::warning('WhatsApp: envio con errores', [
                'status' => $respuesta->status(),
                'error' => $respuesta->json('error') ?? $respuesta->body(),
            ]);

            // 429 / 5xx: temporal. 4xx: plantilla no aprobada, numero invalido, token vencido...
            return ($respuesta->status() === 429 || $respuesta->serverError()) ? 'error' : 'rechazado';

        } catch (Throwable $e) {
            Log::warning('WhatsApp: ' . $e->getMessage());
            return 'error';
        }
    }
}
