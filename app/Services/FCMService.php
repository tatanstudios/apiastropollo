<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Envio de notificaciones con Firebase Cloud Messaging API HTTP v1.
 *
 * Requiere el JSON de una cuenta de servicio del proyecto Firebase
 * (Configuracion de proyecto > Cuentas de servicio > Generar nueva clave privada),
 * cuya ruta se indica en FCM_CREDENCIALES (por defecto storage/app/firebase/cuenta-servicio.json).
 */
class FCMService
{
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    /**
     * Envia una notificacion a uno o varios tokens.
     *
     * @param  string|array  $tokens        token FCM o lista de tokens
     * @param  array         $notification  ['title' => ..., 'body' => ...]
     * @param  array         $data          datos extra (los valores se convierten a string)
     * @return array  [token => true|mensaje de error]
     */
    public static function send($tokens, array $notification, array $data = []): array
    {
        $tokens = array_values(array_filter(is_array($tokens) ? $tokens : [$tokens]));
        if (empty($tokens)) {
            return [];
        }

        $credenciales = self::credenciales();
        $url = 'https://fcm.googleapis.com/v1/projects/' . $credenciales['project_id'] . '/messages:send';
        $accessToken = self::accessToken($credenciales);

        $resultados = [];
        foreach ($tokens as $token) {
            $mensaje = [
                'token' => $token,
                'notification' => array_intersect_key($notification, array_flip(['title', 'body', 'image'])),
                'android' => ['priority' => 'high'],
            ];
            if (!empty($data)) {
                $mensaje['data'] = array_map('strval', $data);
            }

            $respuesta = Http::acceptJson()
                ->withToken($accessToken)
                ->timeout(15)
                ->post($url, ['message' => $mensaje]);

            if ($respuesta->successful()) {
                $resultados[$token] = true;
            } else {
                $error = $respuesta->json('error.message') ?? $respuesta->body();
                Log::warning('FCM v1: fallo envio', ['status' => $respuesta->status(), 'error' => $error]);
                $resultados[$token] = $error;
            }
        }

        return $resultados;
    }

    private static function credenciales(): array
    {
        $ruta = config('googleapi.FCM_Credenciales');
        if (!$ruta || !is_file($ruta)) {
            throw new RuntimeException('FCM v1: no se encontro el JSON de la cuenta de servicio en ' . $ruta);
        }

        $json = json_decode(file_get_contents($ruta), true);
        if (!isset($json['client_email'], $json['private_key'], $json['project_id'])) {
            throw new RuntimeException('FCM v1: JSON de cuenta de servicio invalido');
        }

        return $json;
    }

    /** Token OAuth2 de Google, cacheado ~55 minutos (dura 60). */
    private static function accessToken(array $credenciales): string
    {
        return Cache::remember('fcm_v1_access_token_' . md5($credenciales['client_email']), 3300, function () use ($credenciales) {
            $ahora = time();
            $base64url = fn (string $s) => rtrim(strtr(base64_encode($s), '+/', '-_'), '=');

            $cabecera = $base64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $claims = $base64url(json_encode([
                'iss' => $credenciales['client_email'],
                'scope' => self::SCOPE,
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $ahora,
                'exp' => $ahora + 3600,
            ]));

            $firma = '';
            if (!openssl_sign("$cabecera.$claims", $firma, $credenciales['private_key'], OPENSSL_ALGO_SHA256)) {
                throw new RuntimeException('FCM v1: no se pudo firmar el JWT');
            }
            $jwt = "$cabecera.$claims." . $base64url($firma);

            $respuesta = Http::asForm()->timeout(15)->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if (!$respuesta->successful() || !$respuesta->json('access_token')) {
                throw new RuntimeException('FCM v1: no se obtuvo access token: ' . $respuesta->body());
            }

            return $respuesta->json('access_token');
        });
    }
}
