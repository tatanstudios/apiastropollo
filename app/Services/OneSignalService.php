<?php

namespace App\Services;

use App\Models\UsuariosServicios;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Envio de notificaciones push con OneSignal (API actual: api.onesignal.com).
 *
 * Cada app de OneSignal tiene su propio App ID y su propia App API Key, configurados en
 * config/googleapi.php (seccion OneSignal) a partir del .env:
 *   'cliente'     -> ONE_SIGNAL_APP_CLIENTE / ONE_SIGNAL_KEY_CLIENTE
 *   'restaurante' -> ONE_SIGNAL_APP_RESTAURANTE / ONE_SIGNAL_KEY_RESTAURANTE
 *
 * App del restaurante:
 *   - Version nueva: cada telefono se vincula con OneSignal.login("restaurante_{idUsuario}") (external_id).
 *     Asi un mismo usuario puede estar en varios telefonos y al cerrar sesion deja de recibir.
 *   - Versiones viejas: guardan su id de suscripcion en usuarios_servicios.token_fcm.
 *     La version nueva borra ese token al entrar, para que no le lleguen avisos repetidos.
 */
class OneSignalService
{
    private const URL = 'https://api.onesignal.com/notifications?c=push';
    private const MAX_IDS_POR_ENVIO = 2000;

    /** External ID con el que la app cliente registra cada telefono. */
    public static function externalIdCliente($idCliente): string
    {
        return 'cliente_' . $idCliente;
    }

    /**
     * Envia a clientes: por external_id (app nueva) y por token_fcm (apps viejas).
     * @return string 'enviado' | 'sin_dispositivo' | 'error'
     */
    public static function enviarClientes(array $idsClientes, ?string $titulo, ?string $mensaje, ?string $canal = null): string
    {
        $idsClientes = array_values(array_unique(array_filter($idsClientes)));
        if (empty($idsClientes)) {
            return 'sin_dispositivo';
        }
        $canal = $canal ?? config('googleapi.IdGrupoPasivoCliente');

        $resultados = [];
        $aliases = array_map([self::class, 'externalIdCliente'], $idsClientes);
        foreach (array_chunk($aliases, self::MAX_IDS_POR_ENVIO) as $lote) {
            $resultados[] = self::publicar('cliente', [
                'include_aliases' => ['external_id' => $lote],
                'target_channel' => 'push',
            ], $titulo, $mensaje, $canal);
        }

        $tokens = \App\Models\Clientes::whereIn('id', $idsClientes)
            ->whereNotNull('token_fcm')->where('token_fcm', '!=', '')
            ->pluck('token_fcm')->unique()->values()->all();
        foreach (array_chunk($tokens, self::MAX_IDS_POR_ENVIO) as $lote) {
            $resultados[] = self::publicar('cliente', ['include_subscription_ids' => $lote], $titulo, $mensaje, $canal);
        }

        if (in_array('enviado', $resultados, true)) return 'enviado';
        if (in_array('error', $resultados, true)) return 'error';
        return 'sin_dispositivo';
    }

    /** External ID con el que la app del restaurante registra cada telefono. */
    public static function externalIdRestaurante($idUsuario): string
    {
        return 'restaurante_' . $idUsuario;
    }

    /**
     * Envio por id de suscripcion (lo que guardan token_fcm y la app del cliente).
     *
     * @param  string        $app      'cliente' | 'restaurante'
     * @param  string|array  $ids      id(s) de suscripcion de OneSignal
     * @return bool  true si OneSignal acepto todos los envios
     */
    public static function enviar(string $app, $ids, ?string $titulo, ?string $mensaje, ?string $canal = null): bool
    {
        $ids = array_values(array_unique(array_filter(is_array($ids) ? $ids : [$ids])));
        if (empty($ids)) {
            return false;
        }

        $todoBien = true;
        foreach (array_chunk($ids, self::MAX_IDS_POR_ENVIO) as $lote) {
            $resultado = self::publicar($app, ['include_subscription_ids' => $lote], $titulo, $mensaje, $canal);
            $todoBien = $todoBien && $resultado === 'enviado';
        }
        return $todoBien;
    }

    /**
     * Envia a usuarios de la app del restaurante: por external_id (app nueva) y por token_fcm (apps viejas).
     *
     * @param  array  $idsUsuarios  ids de usuarios_servicios
     * @return string 'enviado' | 'sin_dispositivo' (ningun telefono vinculado) | 'error'
     */
    public static function enviarUsuariosRestaurante(array $idsUsuarios, ?string $titulo, ?string $mensaje, ?string $canal = null): string
    {
        $idsUsuarios = array_values(array_unique(array_filter($idsUsuarios)));
        if (empty($idsUsuarios)) {
            return 'sin_dispositivo';
        }

        $resultados = [];

        // app nueva: external_id
        $aliases = array_map([self::class, 'externalIdRestaurante'], $idsUsuarios);
        foreach (array_chunk($aliases, self::MAX_IDS_POR_ENVIO) as $lote) {
            $resultados[] = self::publicar('restaurante', [
                'include_aliases' => ['external_id' => $lote],
                'target_channel' => 'push',
            ], $titulo, $mensaje, $canal);
        }

        // apps viejas: id de suscripcion guardado
        $tokens = UsuariosServicios::whereIn('id', $idsUsuarios)
            ->whereNotNull('token_fcm')->where('token_fcm', '!=', '')
            ->pluck('token_fcm')->unique()->values()->all();
        foreach (array_chunk($tokens, self::MAX_IDS_POR_ENVIO) as $lote) {
            $resultados[] = self::publicar('restaurante', ['include_subscription_ids' => $lote], $titulo, $mensaje, $canal);
        }

        if (in_array('enviado', $resultados, true)) return 'enviado';
        if (in_array('error', $resultados, true)) return 'error';
        return 'sin_dispositivo';
    }

    /** Envia a todos los usuarios activos de un restaurante (o de varios). */
    public static function enviarRestaurante($idsServicios, ?string $titulo, ?string $mensaje, ?string $canal = null): string
    {
        $idsServicios = is_array($idsServicios) ? $idsServicios : [$idsServicios];
        return self::enviarUsuariosRestaurante(UsuariosServicios::idsActivos($idsServicios), $titulo, $mensaje, $canal);
    }

    /**
     * Hace la peticion a OneSignal.
     * @return string 'enviado' | 'sin_dispositivo' | 'error'
     */
    private static function publicar(string $app, array $destino, ?string $titulo, ?string $mensaje, ?string $canal): string
    {
        $config = config("googleapi.OneSignal.$app");
        if (empty($config['app_id']) || empty($config['api_key'])) {
            Log::warning("OneSignal: falta app_id o api_key para la app '$app' en el .env");
            return 'error';
        }

        $payload = array_merge([
            'app_id' => $config['app_id'],
            'headings' => ['en' => (string) $titulo, 'es' => (string) $titulo],
            'contents' => ['en' => (string) $mensaje, 'es' => (string) $mensaje],
            // FCM prioridad alta: despierta al telefono aunque este en reposo o la app lleve dias sin usarse
            'priority' => 10,
            // si el telefono esta apagado o sin internet, OneSignal lo sigue intentando hasta 1 dia
            'ttl' => 86400,
        ], $destino);
        if (!empty($canal)) {
            $payload['android_channel_id'] = $canal;
        }

        try {
            $respuesta = Http::acceptJson()
                ->withHeaders(['Authorization' => self::cabeceraAuth($config['api_key'])])
                ->timeout(15)
                ->post(self::URL, $payload);

            // con "id" la notificacion salio (a los que si tienen telefono);
            // "errors" puede listar solo los alias/ids que no tienen telefono vinculado
            if ($respuesta->successful() && !empty($respuesta->json('id'))) {
                return 'enviado';
            }

            $errores = $respuesta->json('errors');
            $sinDispositivo = $respuesta->successful() && (
                isset($errores['invalid_aliases']) || isset($errores['invalid_player_ids'])
                || (is_array($errores) && in_array('All included players are not subscribed', $errores, true))
            );
            if ($sinDispositivo) {
                Log::info("OneSignal ($app): sin telefonos vinculados", ['errors' => $errores]);
                return 'sin_dispositivo';
            }

            Log::warning("OneSignal ($app): envio con errores", [
                'status' => $respuesta->status(),
                'errors' => $errores ?? $respuesta->body(),
            ]);
            return 'error';
        } catch (Throwable $e) {
            Log::warning("OneSignal ($app): " . $e->getMessage());
            return 'error';
        }
    }

    /** Las llaves nuevas (os_v2_app_...) usan "Key"; las heredadas usan "Basic". */
    private static function cabeceraAuth(string $apiKey): string
    {
        return (str_starts_with($apiKey, 'os_v2_') ? 'Key ' : 'Basic ') . $apiKey;
    }
}
