<?php

namespace App\Services;

use App\Core\Config;

/**
 * Consume la API pública open.er-api.com para obtener la tasa COP -> USD.
 * Guarda la respuesta en caché (archivo JSON) y, si no hay conexión,
 * usa la última tasa guardada o una tasa de respaldo.
 */
class TasaCambioService
{
    private HttpClient $http;
    private string $archivoCache;

    public function __construct(?HttpClient $http = null, ?string $archivoCache = null)
    {
        $this->http = $http ?? new HttpClient((int) Config::get('api.timeout', 5));
        $this->archivoCache = $archivoCache ?? BASE_PATH . '/storage/cache/tasa_usd.json';
    }

    /**
     * @return array{tasa: float, actualizado: string, fuente: string}
     *         fuente: 'api' (recién consultada), 'cache' o 'respaldo'
     */
    public function obtener(): array
    {
        $cache = $this->leerCache();
        if ($cache !== null && !$this->expirado($cache)) {
            return $cache + ['fuente' => 'cache'];
        }

        $respuesta = $this->http->getJson((string) Config::get('api.tasa_url'));
        if (isset($respuesta['rates']['USD'])) {
            $tasa = [
                'tasa'        => (float) $respuesta['rates']['USD'],
                'actualizado' => date('c'),
            ];
            $this->guardarCache($tasa);
            return $tasa + ['fuente' => 'api'];
        }

        if ($cache !== null) {
            return $cache + ['fuente' => 'cache'];
        }

        return [
            'tasa'        => (float) Config::get('api.tasa_respaldo'),
            'actualizado' => date('c'),
            'fuente'      => 'respaldo',
        ];
    }

    /** Pesos colombianos que equivalen a 1 USD (para mostrar en pantalla). */
    public function pesosPorDolar(): float
    {
        $tasa = $this->obtener()['tasa'];
        return $tasa > 0 ? 1 / $tasa : 0.0;
    }

    public function convertir(float $montoCop): float
    {
        return round($montoCop * $this->obtener()['tasa'], 2);
    }

    private function leerCache(): ?array
    {
        if (!is_file($this->archivoCache)) {
            return null;
        }
        $datos = json_decode((string) file_get_contents($this->archivoCache), true);
        return isset($datos['tasa'], $datos['actualizado']) ? $datos : null;
    }

    private function expirado(array $cache): bool
    {
        $minutos = (int) Config::get('api.cache_minutos', 360);
        return strtotime($cache['actualizado']) < time() - $minutos * 60;
    }

    private function guardarCache(array $datos): void
    {
        @file_put_contents($this->archivoCache, json_encode($datos, JSON_PRETTY_PRINT));
    }
}
