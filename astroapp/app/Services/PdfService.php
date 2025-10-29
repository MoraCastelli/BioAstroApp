<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;

class PdfService {
    public function generarPdfLocal(array $perfil, string $localPath): void {
        $pdf = Pdf::loadView('pdf.paciente', ['perfil' => $perfil])->setPaper('A4');
        file_put_contents($localPath, $pdf->output());
    }
}
