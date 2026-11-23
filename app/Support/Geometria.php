<?php

namespace App\Support;

// poligonos como listas de [lat, lng]; misma regla que el editor de zonas (vistapoligonos.blade.php)
class Geometria
{
    const TOLERANCIA = 1e-7; // ~1 cm: tocarse en el borde o compartir vertices no cuenta como encimarse

    public static function seEnciman(array $p1, array $p2): bool
    {
        foreach ($p1 as $p){ if(self::puntoDentro($p, $p2)) return true; }
        foreach ($p2 as $p){ if(self::puntoDentro($p, $p1)) return true; }

        $n1 = count($p1); $n2 = count($p2);
        for ($i = 0; $i < $n1; $i++){
            for ($j = 0; $j < $n2; $j++){
                if(self::seCruzan($p1[$i], $p1[($i + 1) % $n1], $p2[$j], $p2[($j + 1) % $n2])) return true;
            }
        }
        return false;
    }

    // dentro y no sobre el borde (ray casting)
    public static function puntoDentro(array $p, array $poligono): bool
    {
        $x = $p[1]; $y = $p[0]; $dentro = false; $n = count($poligono);
        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++){
            if(self::distanciaSegmento($p, $poligono[$j], $poligono[$i]) < self::TOLERANCIA) return false;
            [$yi, $xi] = $poligono[$i]; [$yj, $xj] = $poligono[$j];
            if((($yi > $y) !== ($yj > $y)) && ($x < ($xj - $xi) * ($y - $yi) / ($yj - $yi) + $xi)){
                $dentro = !$dentro;
            }
        }
        return $dentro;
    }

    private static function distanciaSegmento(array $p, array $a, array $b): float
    {
        $dx = $b[1] - $a[1]; $dy = $b[0] - $a[0]; $largo = $dx * $dx + $dy * $dy;
        $t = $largo ? max(0, min(1, (($p[1] - $a[1]) * $dx + ($p[0] - $a[0]) * $dy) / $largo)) : 0;
        return hypot($a[1] + $t * $dx - $p[1], $a[0] + $t * $dy - $p[0]);
    }

    private static function orientacion(array $a, array $b, array $c): int
    {
        $v = ($b[1] - $a[1]) * ($c[0] - $a[0]) - ($b[0] - $a[0]) * ($c[1] - $a[1]);
        return abs($v) < self::TOLERANCIA * self::TOLERANCIA ? 0 : ($v > 0 ? 1 : -1);
    }

    // los segmentos se cruzan de verdad (no solo se tocan)
    private static function seCruzan(array $a, array $b, array $c, array $d): bool
    {
        return self::orientacion($a, $b, $c) * self::orientacion($a, $b, $d) < 0
            && self::orientacion($c, $d, $a) * self::orientacion($c, $d, $b) < 0;
    }
}
