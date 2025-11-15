<?php

namespace App\Services;

use App\Support\GoogleRetry;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;

class SheetsService
{
    public function __construct(private readonly Sheets $sheets) {}

    public static function make(): self
    {
        $client = GoogleClientFactory::make();
        return new self(new Sheets($client));
    }

    /* ======================= Helpers de pestañas ======================= */

    /** Asegura que exista una pestaña con $title. Renombra si hay una sola hoja. */
    private function ensureSheet(string $spreadsheetId, string $title): void
    {
        $ss = GoogleRetry::call(fn() =>
            $this->sheets->spreadsheets->get($spreadsheetId, [
                'fields' => 'sheets(properties(sheetId,title))'
            ])
        );
        $sheets = $ss->getSheets() ?? [];

        foreach ($sheets as $s) {
            if (($s->getProperties()->getTitle() ?? '') === $title) {
                return; // ya existe
            }
        }

        if (count($sheets) === 1) {
            $sid = $sheets[0]->getProperties()->getSheetId();
            $requests = [
                new \Google\Service\Sheets\Request([
                    'updateSheetProperties' => [
                        'properties' => ['sheetId' => $sid, 'title' => $title],
                        'fields'     => 'title',
                    ],
                ]),
            ];
            GoogleRetry::call(fn() =>
                $this->sheets->spreadsheets->batchUpdate(
                    $spreadsheetId,
                    new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest(['requests' => $requests])
                )
            );
            return;
        }

        // crear pestaña nueva
        $requests = [
            new \Google\Service\Sheets\Request([
                'addSheet' => ['properties' => ['title' => $title]],
            ]),
        ];
        GoogleRetry::call(fn() =>
            $this->sheets->spreadsheets->batchUpdate(
                $spreadsheetId,
                new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest(['requests' => $requests])
            )
        );
    }

    private function ensurePerfil(string $spreadsheetId): void
    {
        $this->ensureSheet($spreadsheetId, 'Perfil');
    }

    private function ensureEncuentros(string $spreadsheetId): void
    {
        $this->ensureSheet($spreadsheetId, 'Encuentros');

        // Cabeceras en A1:E1
        $headers = new ValueRange([
            'values' => [[
                'FECHA','CIUDAD_ULT_CUMPLE','TEMAS_TRATADOS','RESUMEN','EDAD_EN_ESE_ENCUENTRO'
            ]],
        ]);

        GoogleRetry::call(fn() =>
            $this->sheets->spreadsheets_values->update(
                $spreadsheetId,
                'Encuentros!A1:E1',
                $headers,
                ['valueInputOption' => 'RAW']
            )
        );
    }

    private function ensureIndice(string $spreadsheetId): void
    {
        $this->ensureSheet($spreadsheetId, 'IndicePacientes');

        $headers = new ValueRange([
            'values' => [[ 'NOMBRE_APELLIDO','SPREADSHEET_ID','ULTIMA_ACTUALIZACION' ]],
        ]);

        GoogleRetry::call(fn() =>
            $this->sheets->spreadsheets_values->update(
                $spreadsheetId,
                'IndicePacientes!A1:C1',
                $headers,
                ['valueInputOption' => 'RAW']
            )
        );
    }

    public function ensureIndiceSheet(string $indiceSpreadsheetId): void
    {
        $this->ensureIndice($indiceSpreadsheetId);
    }

    public function readIndice(string $indiceSpreadsheetId): array
    {
        $this->ensureIndice($indiceSpreadsheetId);

        $values = GoogleRetry::call(fn() =>
            $this->sheets->spreadsheets_values
                 ->get($indiceSpreadsheetId, 'IndicePacientes!A2:C10000')
                 ->getValues() ?? []
        );

        return array_values(array_map(fn($r) => [
            'nombre' => $r[0] ?? '',
            'id'     => $r[1] ?? '',
            'ts'     => $r[2] ?? '',
        ], $values));
    }

    /* ======================= Perfil ======================= */

    public function getPerfil(string $spreadsheetId): array
    {
        $this->ensurePerfil($spreadsheetId);

        $range = 'Perfil!A1:B1000';
        $values = GoogleRetry::call(fn() =>
            $this->sheets->spreadsheets_values->get($spreadsheetId, $range)->getValues() ?? []
        );

        $out = [];
        foreach ($values as $row) {
            $k = $row[0] ?? '';
            $v = $row[1] ?? '';
            if ($k !== '') $out[$k] = $v;
        }
        return $out;
    }

    public function setPerfil(string $spreadsheetId, array $kv): void
    {
        $this->ensurePerfil($spreadsheetId);
        $keys = [
            'NOMBRE_Y_APELLIDO','FOTO_URL','CONTACTO','FECHA_NAC','HORA_NAC',
            'CIUDAD_NAC','PROVINCIA_NAC','PAIS_NAC','ANIO_NAC',
            'CIUDAD_ULT_CUMPLE','PROV_ULT_CUMPLE','PAIS_ULT_CUMPLE','OBSERVACIONES',
            'FILTRO_MELLIZOS','FILTRO_ADOPTADO','FILTRO_ABUSOS','FILTRO_SUICIDIO','FILTRO_ENFERMEDAD',
            'SIGNO_SOLAR','FECHA_ENCUENTRO_INICIAL','HORA_ENCUENTRO_INICIAL','EDAD_EN_ENCUENTRO_INICIAL',
            'SIGNO_SUBYACENTE','BALANCE_ENERGETICO','DISPOSITORES','PROGRESIONES_RETORNOS',
            'FASE_LUNACION_NATAL','PLANETA_ASOCIADO_LUNACION',
            'SIGNO_SABIANO','GRADO_SABIANO','TITULO_SABIANO','IMAGEN_SABIANO','TEXTO_SABIANO',
            'SIGNO_SOL','GRADO_SOL','SIGNO_LUNA','GRADO_LUNA',
            'SIGNO_ASOCIADO_LUNACION','IMAGEN_FASE_LUNACION','TEXTO_FASE_LUNACION',
            'PRIMERA_VEZ_ASTROLOGIA','PROFESION','VIVO_CON','HOGAR_INFANCIA','ENF_INFANCIA',
            'SINTOMAS_ACTUALES','MOTIVO_CONSULTA','DETALLE_ENCUENTRO_INICIAL',
            'RESUMEN_PARA_PSICOLOGA_URL_AUDIO','RESUMEN_PARA_PSICOLOGA_TEXTO','ULTIMA_ACTUALIZACION'
        ];

        $rows = array_map(fn($k) => [$k, $kv[$k] ?? ''], $keys);
        $body = new ValueRange(['values' => $rows]);

        GoogleRetry::call(fn() =>
            $this->sheets->spreadsheets_values->update(
                $spreadsheetId,
                'Perfil!A1:B'.count($rows),
                $body,
                ['valueInputOption' => 'RAW']
            )
        );
    }

    /** Inicializa la pestaña Perfil con todas las claves vacías. */
    public function seedPerfil(string $spreadsheetId): void
    {
        $this->ensurePerfil($spreadsheetId);

        $keys = [
            'NOMBRE_Y_APELLIDO','FOTO_URL','CONTACTO','FECHA_NAC','HORA_NAC',
            'CIUDAD_NAC','PROVINCIA_NAC','PAIS_NAC','ANIO_NAC',
            'CIUDAD_ULT_CUMPLE','PROV_ULT_CUMPLE','PAIS_ULT_CUMPLE','OBSERVACIONES',
            'FILTRO_MELLIZOS','FILTRO_ADOPTADO','FILTRO_ABUSOS','FILTRO_SUICIDIO','FILTRO_ENFERMEDAD',
            'SIGNO_SOLAR','FECHA_ENCUENTRO_INICIAL','HORA_ENCUENTRO_INICIAL','EDAD_EN_ENCUENTRO_INICIAL',
            'SIGNO_SUBYACENTE','BALANCE_ENERGETICO','DISPOSITORES','PROGRESIONES_RETORNOS',
            'FASE_LUNACION_NATAL','PLANETA_ASOCIADO_LUNACION',
            'SIGNO_SABIANO','GRADO_SABIANO','TITULO_SABIANO','IMAGEN_SABIANO','TEXTO_SABIANO',
            'SIGNO_SOL','GRADO_SOL','SIGNO_LUNA','GRADO_LUNA',
            'SIGNO_ASOCIADO_LUNACION','IMAGEN_FASE_LUNACION','TEXTO_FASE_LUNACION',
            'PRIMERA_VEZ_ASTROLOGIA','PROFESION','VIVO_CON','HOGAR_INFANCIA','ENF_INFANCIA',
            'SINTOMAS_ACTUALES','MOTIVO_CONSULTA','DETALLE_ENCUENTRO_INICIAL',
            'RESUMEN_PARA_PSICOLOGA_URL_AUDIO','RESUMEN_PARA_PSICOLOGA_TEXTO','ULTIMA_ACTUALIZACION'
        ];


        $rows = array_map(fn($k) => [$k, ''], $keys);
        $body = new ValueRange(['values' => $rows]);

        GoogleRetry::call(fn() =>
            $this->sheets->spreadsheets_values->update(
                $spreadsheetId,
                'Perfil!A1:B'.count($rows),
                $body,
                ['valueInputOption' => 'RAW']
            )
        );
    }

    /* ======================= Encuentros ======================= */

    public function ensureEncuentrosSheet(string $spreadsheetId): void
    {
        $this->ensureEncuentros($spreadsheetId);
    }

    public function appendEncuentro(string $spreadsheetId, array $enc): void
    {
        $this->ensureEncuentros($spreadsheetId);

        $row = [
            $enc['FECHA'] ?? '',
            $enc['CIUDAD_ULT_CUMPLE'] ?? '',
            $enc['TEMAS_TRATADOS'] ?? '',
            $enc['RESUMEN'] ?? '',
            $enc['EDAD_EN_ESE_ENCUENTRO'] ?? '',
        ];
        $body = new ValueRange(['values' => [ $row ]]);

        GoogleRetry::call(fn() =>
            $this->sheets->spreadsheets_values->append(
                $spreadsheetId,
                'Encuentros!A1:E1',
                $body,
                ['valueInputOption' => 'RAW']
            )
        );
    }

    /* ======================= Índice ======================= */

    public function updateIndice(string $indiceSpreadsheetId, array $fila): void
    {
        $this->ensureIndice($indiceSpreadsheetId);

        $body = new ValueRange(['values' => [ $fila ]]);

        GoogleRetry::call(fn() =>
            $this->sheets->spreadsheets_values->append(
                $indiceSpreadsheetId,
                'IndicePacientes!A1:C1',
                $body,
                ['valueInputOption' => 'RAW']
            )
        );
    }

    public function readEncuentros(string $spreadsheetId): array
    {
        // Asegura que exista la hoja y cabeceras
        $this->ensureEncuentrosSheet($spreadsheetId);

        $values = $this->sheets->spreadsheets_values
            ->get($spreadsheetId, 'Encuentros!A2:E100000')
            ->getValues() ?? [];

        // Map a claves esperadas
        return array_values(array_filter(array_map(fn($r) => [
            'FECHA'                   => $r[0] ?? '',
            'CIUDAD_ULT_CUMPLE'       => $r[1] ?? '',
            'TEMAS_TRATADOS'          => $r[2] ?? '',
            'RESUMEN'                 => $r[3] ?? '',
            'EDAD_EN_ESE_ENCUENTRO'   => $r[4] ?? '',
        ], $values), function ($row) {
            // Filtramos filas vacías o que contengan nombres de campos en lugar de fechas
            return !empty($row['FECHA']) && !preg_match('/^[A-Z_]+$/', trim($row['FECHA']));
        }));
    }


    /* ======================= Utilidad ======================= */

    public function createSpreadsheet(string $title): string
    {
        $req = new \Google\Service\Sheets\Spreadsheet([
            'properties' => ['title' => $title],
        ]);

        $sheet = GoogleRetry::call(fn() =>
            $this->sheets->spreadsheets->create($req, ['fields' => 'spreadsheetId'])
        );

        return $sheet->spreadsheetId;
    }

    /* ======================= UTILIDADES DE ADMIN ======================= */

    public function clearRange(string $spreadsheetId, string $range): void
    {
        $clearBody = new \Google\Service\Sheets\ClearValuesRequest();
        \App\Support\GoogleRetry::call(fn() =>
            $this->sheets->spreadsheets_values->clear($spreadsheetId, $range, $clearBody)
        );
    }

    public function writeRange(string $spreadsheetId, string $range, array $values): void
    {
        $body = new \Google\Service\Sheets\ValueRange(['values' => $values]);
        \App\Support\GoogleRetry::call(fn() =>
            $this->sheets->spreadsheets_values->update(
                $spreadsheetId,
                $range,
                $body,
                ['valueInputOption' => 'RAW']
            )
        );
    }


}
