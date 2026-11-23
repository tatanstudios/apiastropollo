<?php

namespace App\Console\Commands;

use App\Services\Imagenes;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Reduce las imágenes ya guardadas que son más grandes de lo necesario.
 * Conserva nombre y formato de cada archivo, así que la base de datos no cambia.
 *
 *   php artisan imagenes:optimizar --simular   (solo muestra cuánto se ahorraría)
 *   php artisan imagenes:optimizar             (sobrescribe los archivos)
 */
class OptimizarImagenes extends Command
{
    protected $signature = 'imagenes:optimizar {--simular : Solo muestra el ahorro, no modifica archivos}';

    protected $description = 'Reduce las imágenes de categorías, productos y banners al tamaño que usa la app';

    public function handle(): int
    {
        $simular = (bool) $this->option('simular');

        // cada imagen con el lado máximo de su uso (si se usa en varios, el mayor)
        $usos = [];
        $agregar = function ($tabla, $lado) use (&$usos) {
            foreach (DB::table($tabla)->whereNotNull('imagen')->pluck('imagen') as $img) {
                $usos[$img] = max($usos[$img] ?? 0, $lado);
            }
        };
        $agregar('categorias', Imagenes::LADO_CATEGORIA);
        $agregar('productos', Imagenes::LADO_PRODUCTO);
        $agregar('slider', Imagenes::LADO_BANNER);

        $this->info(($simular ? '[SIMULACIÓN] ' : '') . 'Revisando ' . count($usos) . ' imágenes en uso...');

        $filas = [];
        $totalAntes = 0;
        $totalDespues = 0;

        foreach ($usos as $nombre => $lado) {
            $resultado = Imagenes::optimizarExistente($nombre, $lado, $simular);
            if (!$resultado) {
                continue;
            }
            [$antes, $despues] = $resultado;
            $totalAntes += $antes;
            $totalDespues += $despues;
            $filas[] = [$nombre, $lado . ' px', self::kb($antes), self::kb($despues)];
        }

        if (empty($filas)) {
            $this->info('No hay imágenes para optimizar.');
            return self::SUCCESS;
        }

        $this->table(['Archivo', 'Lado máx.', 'Antes', 'Después'], $filas);
        $this->info(sprintf('%d imágenes: %s → %s (ahorro %s)%s',
            count($filas), self::kb($totalAntes), self::kb($totalDespues), self::kb($totalAntes - $totalDespues),
            $simular ? ' — no se modificó nada' : ''));

        return self::SUCCESS;
    }

    private static function kb(int $bytes): string
    {
        return $bytes >= 1048576 ? round($bytes / 1048576, 1) . ' MB' : round($bytes / 1024) . ' KB';
    }
}
