<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;

class PdfService
{
    public function generarPdfLocal(array $perfil, string $destino): void
    {
        $html = view('pdf.paciente', compact('perfil'))->render();

        $pdf = Pdf::loadHTML($html)->setPaper('A4', 'portrait');

        @mkdir(dirname($destino), 0777, true);
        file_put_contents($destino, $pdf->output());
    }
}
