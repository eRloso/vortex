<?php

namespace App\Services;

/**
 * Cliente HTTP mínimo para consumir APIs externas.
 * Usa cURL si está disponible; si no, file_get_contents.
 */
class HttpClient
{
    public function __construct(private int $timeout = 5)
    {
    }

    /** Hace un GET y devuelve el JSON decodificado, o null si falla. */
    public function getJson(string $url): ?array
    {
        $respuesta = function_exists('curl_init') ? $this->conCurl($url) : $this->conStream($url);
        if ($respuesta === null) {
            return null;
        }

        $datos = json_decode($respuesta, true);
        return is_array($datos) ? $datos : null;
    }

    private function conCurl(string $url): ?string
    {
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);
        $cuerpo = curl_exec($curl);
        $codigo = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        return ($cuerpo === false || $codigo >= 400) ? null : (string) $cuerpo;
    }

    private function conStream(string $url): ?string
    {
        $contexto = stream_context_create(['http' => ['timeout' => $this->timeout, 'header' => 'Accept: application/json']]);
        $cuerpo = @file_get_contents($url, false, $contexto);
        return $cuerpo === false ? null : $cuerpo;
    }
}
