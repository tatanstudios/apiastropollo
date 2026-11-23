<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Guarda imágenes subidas desde el panel en el disco "imagenes", ya reducidas.
 *
 * - Verifica que el archivo sea realmente JPG o PNG (por su contenido, no por el nombre).
 * - Si es más grande que el lado máximo del uso, la reduce manteniendo la proporción.
 * - JPG se guarda con calidad 82 y corrigiendo la rotación de fotos de celular.
 * - PNG con transparencia se mantiene PNG; PNG sin transparencia (fotos) se pasa a JPG.
 *
 * Así una foto de 4 MB queda en unos cientos de KB y la app descarga menos datos.
 */
class Imagenes
{
    /** Peso máximo aceptado al subir (KB). Se usa en las reglas de validación. */
    public const PESO_MAXIMO_KB = 8192;

    /** Lado máximo (px) según dónde se muestra la imagen en la app. */
    public const LADO_CATEGORIA = 600;
    public const LADO_PRODUCTO = 1000;
    public const LADO_BANNER = 1600;

    private const CALIDAD_JPG = 82;

    /** 40 megapíxeles: más que cualquier celular; evita agotar la memoria al descomprimir. */
    private const PIXELES_MAXIMOS = 40000000;

    /** GD necesita ~5 bytes por píxel; se sube el límite de memoria solo si es menor a 512 MB. */
    private static function asegurarMemoria(): void
    {
        $actual = ini_get('memory_limit');
        if ($actual === '-1') {
            return;
        }
        $bytes = (int) $actual * ([ 'G' => 1073741824, 'M' => 1048576, 'K' => 1024 ][strtoupper(substr($actual, -1))] ?? 1);
        if ($bytes < 536870912) {
            @ini_set('memory_limit', '512M');
        }
    }

    /**
     * Valida el archivo "imagen" del request. Devuelve el mensaje de error o null si está bien.
     * Solo mira el archivo real (algunos formularios mandan el texto "undefined" si no hay imagen).
     */
    public static function validar(\Illuminate\Http\Request $request, bool $requerida = false, string $campo = 'imagen'): ?string
    {
        if (!$request->hasFile($campo)) {
            return $requerida ? 'La imagen es requerida' : null;
        }

        $validar = \Illuminate\Support\Facades\Validator::make(
            [$campo => $request->file($campo)],
            [$campo => 'file|mimes:jpg,jpeg,png|max:' . self::PESO_MAXIMO_KB]
        );

        return $validar->fails() ? self::mensajeError() : null;
    }

    /** Mensaje para el usuario cuando la imagen no es válida. */
    public static function mensajeError(): string
    {
        return 'La imagen debe ser JPG o PNG y pesar máximo ' . (self::PESO_MAXIMO_KB / 1024) . ' MB';
    }

    /**
     * Valida, reduce y guarda. Devuelve el nombre del archivo guardado o null si no es una imagen válida.
     */
    public static function guardar(UploadedFile $archivo, int $ladoMaximo): ?string
    {
        $info = @getimagesize($archivo->getRealPath());
        if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) {
            return null; // no es JPG/PNG de verdad (aunque el nombre lo diga)
        }
        if ($info[0] * $info[1] > self::PIXELES_MAXIMOS) {
            return null; // demasiado grande para procesarla en memoria
        }

        self::asegurarMemoria();

        try {
            $origen = $info[2] === IMAGETYPE_JPEG
                ? @imagecreatefromjpeg($archivo->getRealPath())
                : @imagecreatefrompng($archivo->getRealPath());
            if (!$origen) {
                return null;
            }

            if ($info[2] === IMAGETYPE_JPEG) {
                $origen = self::corregirOrientacion($origen, $archivo->getRealPath());
            }

            $imagen = self::reducir($origen, $ladoMaximo);

            // PNG con transparencia se queda PNG; lo demás se guarda como JPG
            $esPng = $info[2] === IMAGETYPE_PNG && self::tieneTransparencia($imagen);
            $nombre = str_replace([' ', '.'], '_', Str::random(15) . microtime()) . ($esPng ? '.png' : '.jpg');

            $contenido = self::codificar($imagen, $esPng);
            imagedestroy($imagen);

            return Storage::disk('imagenes')->put($nombre, $contenido) ? $nombre : null;
        } catch (\Throwable $e) {
            Log::error('Imagenes: no se pudo procesar la imagen', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Reduce una imagen ya guardada en el disco, conservando nombre y formato (para el comando de optimizar).
     * Devuelve [pesoAntes, pesoDespues] o null si no se tocó.
     */
    public static function optimizarExistente(string $nombre, int $ladoMaximo, bool $simular = false): ?array
    {
        $disco = Storage::disk('imagenes');
        if (!$disco->exists($nombre)) {
            return null;
        }

        $ruta = $disco->path($nombre);
        $info = @getimagesize($ruta);
        if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) {
            return null;
        }

        if ($info[0] * $info[1] > self::PIXELES_MAXIMOS) {
            return null;
        }
        self::asegurarMemoria();

        $antes = filesize($ruta);
        $esPng = $info[2] === IMAGETYPE_PNG;
        $grande = max($info[0], $info[1]) > $ladoMaximo;

        // solo vale la pena si es más grande de lo necesario o pesa mucho
        if (!$grande && $antes < 300 * 1024) {
            return null;
        }

        $origen = $esPng ? @imagecreatefrompng($ruta) : @imagecreatefromjpeg($ruta);
        if (!$origen) {
            return null;
        }
        if (!$esPng) {
            $origen = self::corregirOrientacion($origen, $ruta);
        }

        $imagen = self::reducir($origen, $ladoMaximo);
        $contenido = self::codificar($imagen, $esPng);
        imagedestroy($imagen);

        $despues = strlen($contenido);
        if ($despues >= $antes) {
            return null; // no mejora
        }

        if (!$simular) {
            file_put_contents($ruta, $contenido);
        }

        return [$antes, $despues];
    }

    /** Reduce al lado máximo manteniendo proporción y transparencia. Libera la imagen original. */
    private static function reducir($origen, int $ladoMaximo)
    {
        $ancho = imagesx($origen);
        $alto = imagesy($origen);
        $escala = min(1, $ladoMaximo / max($ancho, $alto));

        if ($escala >= 1) {
            imagealphablending($origen, false);
            imagesavealpha($origen, true);
            return $origen;
        }

        $nuevoAncho = max(1, (int) round($ancho * $escala));
        $nuevoAlto = max(1, (int) round($alto * $escala));

        $destino = imagecreatetruecolor($nuevoAncho, $nuevoAlto);
        imagealphablending($destino, false);
        imagesavealpha($destino, true);
        imagefill($destino, 0, 0, imagecolorallocatealpha($destino, 0, 0, 0, 127));
        imagecopyresampled($destino, $origen, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto);
        imagedestroy($origen);

        return $destino;
    }

    /** Bytes de la imagen en PNG (máxima compresión) o JPG (fondo blanco si tenía transparencia). */
    private static function codificar($imagen, bool $png): string
    {
        ob_start();
        if ($png) {
            imagepng($imagen, null, 9);
        } else {
            $ancho = imagesx($imagen);
            $alto = imagesy($imagen);
            $fondo = imagecreatetruecolor($ancho, $alto);
            imagefill($fondo, 0, 0, imagecolorallocate($fondo, 255, 255, 255));
            imagecopy($fondo, $imagen, 0, 0, 0, 0, $ancho, $alto);
            imageinterlace($fondo, true); // carga progresiva
            imagejpeg($fondo, null, self::CALIDAD_JPG);
            imagedestroy($fondo);
        }
        return ob_get_clean();
    }

    /** true si algún píxel es (semi)transparente. */
    private static function tieneTransparencia($imagen): bool
    {
        $ancho = imagesx($imagen);
        $alto = imagesy($imagen);
        // con imágenes grandes se revisa una muestra; ya están reducidas, así que es rápido
        $paso = max(1, (int) floor(max($ancho, $alto) / 400));

        for ($y = 0; $y < $alto; $y += $paso) {
            for ($x = 0; $x < $ancho; $x += $paso) {
                if (((imagecolorat($imagen, $x, $y) >> 24) & 0x7F) > 0) {
                    return true;
                }
            }
        }
        return false;
    }

    /** Las fotos de celular traen la rotación en EXIF; se aplica para que no queden de lado. */
    private static function corregirOrientacion($imagen, string $ruta)
    {
        if (!function_exists('exif_read_data')) {
            return $imagen;
        }
        try {
            // algunos JPG traen EXIF mal formado: se ignora y se deja la imagen como viene
            $exif = @exif_read_data($ruta);
        } catch (\Throwable $e) {
            return $imagen;
        }
        if (!is_array($exif)) {
            return $imagen;
        }
        $grados = [3 => 180, 6 => -90, 8 => 90][$exif['Orientation'] ?? 1] ?? 0;
        if ($grados === 0) {
            return $imagen;
        }
        $rotada = imagerotate($imagen, $grados, 0);
        if ($rotada) {
            imagedestroy($imagen);
            return $rotada;
        }
        return $imagen;
    }
}
