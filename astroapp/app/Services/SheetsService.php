<?php

namespace App\Services;

use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;

class SheetsService {
    public function __construct(private readonly Sheets $sheets) {}

    public static function make(): self {
        $client = GoogleClientFactory::make();
        return new self(new Sheets($client));
    }

    public function getPerfil(string $spreadsheetId): array {
        $range = 'Perfil!A1:B39';
        $values = $this->sheets->spreadsheets_values->get($spreadsheetId, $range)->getValues() ?? [];
        $out = [];
        foreach ($values as $row) {
            $k = $row[0] ?? '';
            $v = $row[1] ?? '';
            if ($k !== '') $out[$k] = $v;
        }
        return $out;
    }

    public function setPerfil(string $spreadsheetId, array $kv): void {
        // Espera claves EXACTAS en la columna A del template (A1..A39).
        // Construye un arreglo de dos columnas A/B en el mismo orden.
        $keys = [
            'NOMBRE_Y_APELLIDO','FOTO_URL','CONTACTO','FECHA_NAC','HORA_NAC',
            'CIUDAD_NAC','PROVINCIA_NAC','PAIS_NAC','ANIO_NAC',
            'CIUDAD_ULT_CUMPLE','PROV_ULT_CUMPLE','PAIS_ULT_CUMPLE','OBSERVACIONES',
            'FILTRO_MELLIZOS','FILTRO_ADOPTADO','FILTRO_ABUSOS','FILTRO_SUICIDIO','FILTRO_ENFERMEDAD',
            'SIGNO_SOLAR','FECHA_ENCUENTRO_INICIAL','HORA_ENCUENTRO_INICIAL','EDAD_EN_ENCUENTRO_INICIAL',
            'SIGNO_SUBYACENTE','BALANCE_ENERGETICO','DISPOSITORES','PROGRESIONES_RETORNOS',
            'FASE_LUNACION_NATAL','PLANETA_ASOCIADO_LUNACION','PRIMERA_VEZ_ASTROLOGIA','PROFESION',
            'VIVO_CON','HOGAR_INFANCIA','ENF_INFANCIA','SINTOMAS_ACTUALES','MOTIVO_CONSULTA',
            'DETALLE_ENCUENTRO_INICIAL','RESUMEN_PARA_PSICOLOGA_URL_AUDIO','RESUMEN_PARA_PSICOLOGA_TEXTO','ULTIMA_ACTUALIZACION'
        ];
        $rows = array_map(fn($k) => [$k, $kv[$k] ?? ''], $keys);
        $body = new ValueRange(['values' => $rows]);
        $this->sheets->spreadsheets_values->update($spreadsheetId, 'Perfil!A1:B39', $body, ['valueInputOption' => 'RAW']);
    }

    public function appendEncuentro(string $spreadsheetId, array $enc): void {
        $row = [
            $enc['FECHA'] ?? '',
            $enc['CIUDAD_ULT_CUMPLE'] ?? '',
            $enc['TEMAS_TRATADOS'] ?? '',
            $enc['RESUMEN'] ?? '',
            $enc['EDAD_EN_ESE_ENCUENTRO'] ?? '',
        ];
        $body = new ValueRange(['values' => [ $row ]]);
        $this->sheets->spreadsheets_values->append($spreadsheetId, 'Encuentros!A1:E1', $body, ['valueInputOption' => 'RAW']);
    }

    public function updateIndice(string $indiceSpreadsheetId, array $fila): void {
        // fila = [NOMBRE_APELLIDO, SPREADSHEET_ID, ULTIMA_ACTUALIZACION]
        $body = new ValueRange(['values' => [ $fila ]]);
        $this->sheets->spreadsheets_values->append($indiceSpreadsheetId, 'IndicePacientes!A1:C1', $body, ['valueInputOption' => 'RAW']);
    }
}
