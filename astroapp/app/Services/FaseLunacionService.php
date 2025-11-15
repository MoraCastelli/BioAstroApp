<?php

namespace App\Services;

class FaseLunacionService
{
    /**
     * Convierte signo + grado en grados absolutos dentro del zodíaco.
     */
    private function zodiacToDegrees(string $signo, int $grado): ?float
    {
        $signo = strtolower(trim($signo));

        $offsets = [
            'aries' => 0,
            'tauro' => 30,
            'géminis' => 60, 'geminis' => 60,
            'cáncer' => 90, 'cancer' => 90,
            'leo' => 120,
            'virgo' => 150,
            'libra' => 180,
            'escorpio' => 210,
            'sagitario' => 240,
            'capricornio' => 270,
            'acuario' => 300,
            'piscis' => 330,
        ];

        if (!isset($offsets[$signo])) {
            return null;
        }

        return $offsets[$signo] + $grado;
    }

    /**
     * Calcula el nombre de la fase usando las reglas astrológicas reales.
     */
    public function calcular(string $signoSol, int $gradoSol, string $signoLuna, int $gradoLuna): ?string
    {
        $sol = $this->zodiacToDegrees($signoSol, $gradoSol);
        $luna = $this->zodiacToDegrees($signoLuna, $gradoLuna);

        if ($sol === null || $luna === null) {
            return null;
        }

        $angulo = fmod(($luna - $sol + 360), 360);

        if ($angulo < 45)  return 'Luna Nueva';
        if ($angulo < 90)  return 'Luna Creciente';
        if ($angulo < 135) return 'Luna Cuarto Creciente';
        if ($angulo < 180) return 'Luna Gibosa';
        if ($angulo < 225) return 'Luna Llena';
        if ($angulo < 270) return 'Luna Menguante';
        if ($angulo < 315) return 'Luna Cuarto Menguante';

        return 'Luna Balsamica';
    }
}
