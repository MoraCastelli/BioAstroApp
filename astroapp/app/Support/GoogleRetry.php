<?php

namespace App\Support;

use Google\Service\Exception as GoogleServiceException;

class GoogleRetry
{
    /**
     * Ejecuta $fn con reintentos ante 503/500/429 o errores de red.
     * $times = intentos totales (p. ej. 5), $baseMs = base del backoff (p. ej. 200ms).
     */
    public static function call(callable $fn, int $times = 5, int $baseMs = 200)
    {
        $attempt = 0;
        start:
        try {
            return $fn();
        } catch (\Throwable $e) {
            $attempt++;

            // ¿Es reintetable?
            $retryable = false;

            if ($e instanceof GoogleServiceException) {
                $code = $e->getCode();
                // 503 (UNAVAILABLE), 500 (internal), 429 (rate limit)
                if (in_array($code, [429, 500, 503], true)) {
                    $retryable = true;
                }
            } else {
                // errores de red típicos (time out, reset, DNS) -> probamos reintentar
                $msg = strtolower($e->getMessage() ?? '');
                foreach (['timeout', 'timed out', 'connection reset', 'could not resolve host'] as $needle) {
                    if (str_contains($msg, $needle)) { $retryable = true; break; }
                }
            }

            if ($retryable && $attempt < $times) {
                // backoff exponencial con jitter
                $sleepMs = (int) (($baseMs * (2 ** ($attempt - 1))) + random_int(0, 150));
                usleep($sleepMs * 1000);
                goto start;
            }

            // si no es reintetable o agotamos intentos, re-lanzamos
            throw $e;
        }
    }
}
