<?php

namespace App\Services;

use App\Support\GoogleRetry;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;
use Illuminate\Support\Facades\Storage;

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

        $headers = new ValueRange([
            'values' => [[ 'CLAVE', 'VALOR' ]],
        ]);

        GoogleRetry::call(fn() =>
            $this->sheets->spreadsheets_values->update(
                $spreadsheetId,
                'Perfil!A1:B1',
                $headers,
                ['valueInputOption' => 'RAW']
            )
        );
    }


    private function ensureEncuentros(string $spreadsheetId): void
    {
        $this->ensureSheet($spreadsheetId, 'Encuentros');

        $headers = new ValueRange([
            'values' => [[
                'NRO_DE_ENCUENTRO','FECHA','EDAD_EN_ESE_ENCUENTRO','CIUDAD_ULT_CUMPLE','TEMAS_TRATADOS','RESUMEN'
            ]],
        ]);

        GoogleRetry::call(fn() =>
            $this->sheets->spreadsheets_values->update(
                $spreadsheetId,
                'Encuentros!A1:F1',
                $headers,
                ['valueInputOption' => 'RAW']
            )
        );
    }

    private function ensureImagenes(string $spreadsheetId): void
    {
        $this->ensureSheet($spreadsheetId, 'Imagenes');

        $headers = new ValueRange([
            'values' => [[ 'NOMBRE_IMAGEN','URL','DESCRIPCION' ]],
        ]);

        GoogleRetry::call(fn() =>
            $this->sheets->spreadsheets_values->update(
                $spreadsheetId,
                'Imagenes!A1:C1',
                $headers,
                ['valueInputOption' => 'RAW']
            )
        );
    }

    private function ensureIndice(string $spreadsheetId): void
    {
        $this->ensureSheet($spreadsheetId, 'IndicePacientes');

        $headers = new ValueRange([
            'values' => [[
                'NOMBRE_APELLIDO',
                'SPREADSHEET_ID',
                'ULTIMA_ACTUALIZACION',
                'FOLDER_ID',
                'IMAGENES_FOLDER_ID',
            ]],
        ]);

        GoogleRetry::call(fn() =>
            $this->sheets->spreadsheets_values->update(
                $spreadsheetId,
                'IndicePacientes!A1:E1',
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
                ->get($indiceSpreadsheetId, 'IndicePacientes!A2:E10000')
                ->getValues() ?? []
        );

        return array_values(array_filter(array_map(fn($r) => [
            'nombre' => $r[0] ?? '',
            'id'     => $r[1] ?? '',
            'ts'     => $r[2] ?? '',
            'folder_id' => $r[3] ?? '',
            'imagenes_folder_id' => $r[4] ?? '',
        ], $values), fn($row) => trim($row['id']) !== ''));

    }

    /* ======================= Perfil ======================= */

    public function getPerfil(string $spreadsheetId): array
    {
        $this->ensurePerfil($spreadsheetId);

        $values = GoogleRetry::call(fn() =>
            $this->sheets->spreadsheets_values->get($spreadsheetId, 'Perfil!A2:B1000')->getValues() ?? []
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
            'FILTRO_MELLIZOS','FILTRO_ADOPTADO','FILTRO_ABUSOS','FILTRO_SUICIDIO','FILTRO_SALUD',
            'FILTRO_TEA','FILTRO_HISTORICOS','FILTRO_FILOSOFOS','FILTRO_PAISES','FILTRO_ECLIPSES',
            'FILTRO_ANUALES','FILTRO_MOMENTOS_CRITICOS','FILTRO_INICIO_CICLOS','SIGNO_SOLAR',
            'SIGNO_SUBYACENTE','BALANCE_ENERGETICO','DISPOSITORES','PROGRESIONES_RETORNOS',
            'FASE_LUNACION_NATAL','PLANETA_ASOCIADO_LUNACION',
            'SIGNO_SABIANO','GRADO_SABIANO','TITULO_SABIANO','IMAGEN_SABIANO','TEXTO_SABIANO',
            'SIGNO_SOL','GRADO_SOL','SIGNO_LUNA','GRADO_LUNA',
            'SIGNO_ASOCIADO_LUNACION','IMAGEN_FASE_LUNACION','TEXTO_FASE_LUNACION',
            'PRIMERA_VEZ_ASTROLOGIA','PROFESION','VIVO_CON','HOGAR_INFANCIA','ENF_INFANCIA',
            'SINTOMAS_ACTUALES','MOTIVO_CONSULTA',
            'RESUMEN_PARA_PSICOLOGA_URL_AUDIO','RESUMEN_PARA_PSICOLOGA_TEXTO',
            'ULTIMA_ACTUALIZACION'
        ];

        // filas: KEY | VALUE desde la fila 2
        $rows = array_map(fn($k) => [$k, $kv[$k] ?? ''], $keys);

        // escribimos header + rows
        $body = new ValueRange(['values' => array_merge([['CLAVE','VALOR']], $rows)]);

        GoogleRetry::call(fn() =>
            $this->sheets->spreadsheets_values->update(
                $spreadsheetId,
                'Perfil!A1:B'.(count($rows)+1),
                $body,
                ['valueInputOption' => 'RAW']
            )
        );
    }


    public function seedPerfil(string $spreadsheetId): void
    {
        $this->ensurePerfil($spreadsheetId);

        $keys = [
            'NOMBRE_Y_APELLIDO','FOTO_URL','CONTACTO','FECHA_NAC','HORA_NAC',
            'CIUDAD_NAC','PROVINCIA_NAC','PAIS_NAC','ANIO_NAC',
            'CIUDAD_ULT_CUMPLE','PROV_ULT_CUMPLE','PAIS_ULT_CUMPLE','OBSERVACIONES',
            'FILTRO_MELLIZOS','FILTRO_ADOPTADO','FILTRO_ABUSOS','FILTRO_SUICIDIO','FILTRO_SALUD',
            'FILTRO_TEA','FILTRO_HISTORICOS','FILTRO_FILOSOFOS','FILTRO_PAISES','FILTRO_ECLIPSES',
            'FILTRO_ANUALES','FILTRO_MOMENTOS_CRITICOS','FILTRO_INICIO_CICLOS','SIGNO_SOLAR',
            'SIGNO_SUBYACENTE','BALANCE_ENERGETICO','DISPOSITORES','PROGRESIONES_RETORNOS',
            'FASE_LUNACION_NATAL','PLANETA_ASOCIADO_LUNACION',
            'SIGNO_SABIANO','GRADO_SABIANO','TITULO_SABIANO','IMAGEN_SABIANO','TEXTO_SABIANO',
            'SIGNO_SOL','GRADO_SOL','SIGNO_LUNA','GRADO_LUNA',
            'SIGNO_ASOCIADO_LUNACION','IMAGEN_FASE_LUNACION','TEXTO_FASE_LUNACION',
            'PRIMERA_VEZ_ASTROLOGIA','PROFESION','VIVO_CON','HOGAR_INFANCIA','ENF_INFANCIA',
            'SINTOMAS_ACTUALES','MOTIVO_CONSULTA',
            'RESUMEN_PARA_PSICOLOGA_URL_AUDIO','RESUMEN_PARA_PSICOLOGA_TEXTO',
            'ULTIMA_ACTUALIZACION'
        ];

        $rows = array_map(fn($k) => [$k, ''], $keys);
        $body = new ValueRange(['values' => array_merge([['CLAVE','VALOR']], $rows)]);

        GoogleRetry::call(fn() =>
            $this->sheets->spreadsheets_values->update(
                $spreadsheetId,
                'Perfil!A1:B'.(count($rows)+1),
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

    private function nextEncuentroNro(string $spreadsheetId): int
    {
        $this->ensureEncuentros($spreadsheetId);

        $values = GoogleRetry::call(fn() =>
            $this->sheets->spreadsheets_values
                ->get($spreadsheetId, 'Encuentros!A2:A100000')
                ->getValues() ?? []
        );

        // cuenta filas no vacías en col A
        $count = 0;
        foreach ($values as $r) {
            if (!empty($r[0])) $count++;
        }
        return $count + 1;
    }

    public function appendEncuentro(string $spreadsheetId, array $enc): void
    {
        $this->ensureEncuentros($spreadsheetId);

        $nro = $enc['NRO_DE_ENCUENTRO'] ?? $this->nextEncuentroNro($spreadsheetId);

        $row = [
            $nro,
            $enc['FECHA'] ?? '',
            $enc['EDAD_EN_ESE_ENCUENTRO'] ?? '',
            $enc['CIUDAD_ULT_CUMPLE'] ?? '',
            $enc['TEMAS_TRATADOS'] ?? '',
            $enc['RESUMEN'] ?? '',
        ];

        $body = new ValueRange(['values' => [ $row ]]);

        GoogleRetry::call(fn() =>
            $this->sheets->spreadsheets_values->append(
                $spreadsheetId,
                'Encuentros!A1:F1',
                $body,
                ['valueInputOption' => 'RAW', 'insertDataOption' => 'INSERT_ROWS']
            )
        );
    }

    public function appendImagen(string $spreadsheetId, array $img): void
    {
        $this->ensureImagenes($spreadsheetId);

        $row = [
            $img['NOMBRE_IMAGEN'] ?? '',
            $img['URL'] ?? '',
            $img['DESCRIPCION'] ?? '',
        ];

        $body = new ValueRange(['values' => [ $row ]]);

        GoogleRetry::call(fn() =>
            $this->sheets->spreadsheets_values->append(
                $spreadsheetId,
                'Imagenes!A1:C1',
                $body,
                ['valueInputOption' => 'RAW', 'insertDataOption' => 'INSERT_ROWS']
            )
        );
    }

    public function readImagenes(string $spreadsheetId): array
    {
        $this->ensureImagenes($spreadsheetId);

        $values = GoogleRetry::call(fn() =>
            $this->sheets->spreadsheets_values
                ->get($spreadsheetId, 'Imagenes!A2:C100000')
                ->getValues() ?? []
        );

        return array_values(array_filter(array_map(fn($r) => [
            'NOMBRE_IMAGEN' => $r[0] ?? '',
            'URL'           => $r[1] ?? '',
            'DESCRIPCION'   => $r[2] ?? '',
        ], $values), fn($row) => trim((string)$row['URL']) !== ''));
    }

    public function readImagenesWithRows(string $spreadsheetId): array
    {
        $this->ensureImagenes($spreadsheetId);

        $resp = GoogleRetry::call(fn() =>
            $this->sheets->spreadsheets_values->get($spreadsheetId, 'Imagenes!A2:C100000')
        );

        $values = $resp->getValues() ?? [];

        $out = [];
        foreach ($values as $i => $r) {
            $url = trim((string)($r[1] ?? ''));
            if ($url === '') continue;

            $out[] = [
                'row'           => 2 + $i, // fila real
                'NOMBRE_IMAGEN' => $r[0] ?? '',
                'URL'           => $r[1] ?? '',
                'DESCRIPCION'   => $r[2] ?? '',
            ];
        }

        return $out;
    }

    public function deleteImagenRow(string $spreadsheetId, int $row): void
    {
        $this->ensureImagenes($spreadsheetId);

        // necesitamos el sheetId numérico de "Imagenes"
        $ss = GoogleRetry::call(fn() =>
            $this->sheets->spreadsheets->get($spreadsheetId, [
                'fields' => 'sheets(properties(sheetId,title))'
            ])
        );

        $sheetId = null;
        foreach (($ss->getSheets() ?? []) as $s) {
            if (($s->getProperties()->getTitle() ?? '') === 'Imagenes') {
                $sheetId = $s->getProperties()->getSheetId();
                break;
            }
        }
        if ($sheetId === null) return;

        // row en API es 0-based y endRowIndex es exclusivo
        $start = max(0, $row - 1);
        $end   = $start + 1;

        $requests = [
            new \Google\Service\Sheets\Request([
                'deleteDimension' => [
                    'range' => [
                        'sheetId' => $sheetId,
                        'dimension' => 'ROWS',
                        'startIndex' => $start,
                        'endIndex' => $end,
                    ],
                ],
            ]),
        ];

        GoogleRetry::call(fn() =>
            $this->sheets->spreadsheets->batchUpdate(
                $spreadsheetId,
                new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest(['requests' => $requests])
            )
        );
    }



    public function updateImagenDescripcion(string $spreadsheetId, int $row, string $descripcion): void
    {
        $this->ensureImagenes($spreadsheetId);

        $body = new \Google\Service\Sheets\ValueRange([
            'values' => [[ $descripcion ]]
        ]);

        GoogleRetry::call(fn() =>
            $this->sheets->spreadsheets_values->update(
                $spreadsheetId,
                "Imagenes!C{$row}",
                $body,
                ['valueInputOption' => 'RAW']
            )
        );
    }

    public function renameSpreadsheet(string $spreadsheetId, string $newTitle): void
    {
        $requests = [
            new \Google\Service\Sheets\Request([
                'updateSpreadsheetProperties' => [
                    'properties' => ['title' => $newTitle],
                    'fields' => 'title',
                ],
            ]),
        ];

        GoogleRetry::call(fn() =>
            $this->sheets->spreadsheets->batchUpdate(
                $spreadsheetId,
                new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest(['requests' => $requests])
            )
        );
    }


    public function createPacienteLibroDesdeTemplateEnCarpeta(
        string $templateSpreadsheetId,
        string $folderId,
        string $tempName = 'Paciente (sin nombre)'
    ): string {
        $newSpreadsheetId = DriveService::make()->copyFileToFolder($templateSpreadsheetId, $tempName, $folderId);

        $this->ensurePerfil($newSpreadsheetId);
        $this->ensureEncuentros($newSpreadsheetId);
        $this->ensureImagenes($newSpreadsheetId);

        return $newSpreadsheetId;
    }



    /* ======================= Índice ======================= */

    public function upsertIndice(string $indiceSpreadsheetId, array $fila): void
    {
        $this->ensureIndice($indiceSpreadsheetId);

        // fila esperada: [nombre, spreadsheetId, ts, ...]
        $nombre = $fila[0] ?? '';
        $id     = $fila[1] ?? '';
        $ts     = $fila[2] ?? '';

        if (!$id) return;

        $range = 'IndicePacientes!A2:C10000';
        $values = GoogleRetry::call(fn() =>
            $this->sheets->spreadsheets_values->get($indiceSpreadsheetId, $range)->getValues() ?? []
        );

        // buscar id existente en columna B
        $rowIndex = null; // 0-based sobre values
        foreach ($values as $i => $r) {
            if (($r[1] ?? '') === $id) {
                $rowIndex = $i;
                break;
            }
        }

        if ($rowIndex !== null) {
            // update en la fila existente
            $targetRow = 2 + $rowIndex; // porque empieza en A2
            $body = new \Google\Service\Sheets\ValueRange([
                'values' => [[ $nombre, $id, $ts ]]
            ]);

            GoogleRetry::call(fn() =>
                $this->sheets->spreadsheets_values->update(
                    $indiceSpreadsheetId,
                    "IndicePacientes!A{$targetRow}:C{$targetRow}",
                    $body,
                    ['valueInputOption' => 'RAW']
                )
            );
        } else {
            // append si no existe
            $body = new \Google\Service\Sheets\ValueRange(['values' => [[$nombre, $id, $ts]]]);

            GoogleRetry::call(fn() =>
                $this->sheets->spreadsheets_values->append(
                    $indiceSpreadsheetId,
                    'IndicePacientes!A1:C1',
                    $body,
                    ['valueInputOption' => 'RAW', 'insertDataOption' => 'INSERT_ROWS']
                )
            );
        }
    }


    public function readEncuentros(string $spreadsheetId): array
    {
        $this->ensureEncuentrosSheet($spreadsheetId);

        $values = GoogleRetry::call(fn() =>
            $this->sheets->spreadsheets_values
                ->get($spreadsheetId, 'Encuentros!A2:F100000')
                ->getValues() ?? []
        );

        return array_values(array_filter(array_map(fn($r) => [
            'NRO_DE_ENCUENTRO'         => $r[0] ?? '',
            'FECHA'                   => $r[1] ?? '',
            'EDAD_EN_ESE_ENCUENTRO'   => $r[2] ?? '',
            'CIUDAD_ULT_CUMPLE'       => $r[3] ?? '',
            'TEMAS_TRATADOS'          => $r[4] ?? '',
            'RESUMEN'                 => $r[5] ?? '',
        ], $values), function ($row) {
            return !empty($row['FECHA']) && !preg_match('/^[A-Z_]+$/', trim((string)$row['FECHA']));
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

    /* ======================= Sabianos (múltiples) ======================= */

    public function ensureSabianosSheet(string $spreadsheetId): void
    {
        // Esto ya crea o renombra usando tu helper real ✅
        $this->ensureSheet($spreadsheetId, 'Sabianos');

        // Header
        $headers = new ValueRange([
            'values' => [[ 'FECHA','SIGNO','GRADO','TITULO','TEXTO','IMAGEN' ]],
        ]);

        GoogleRetry::call(fn() =>
            $this->sheets->spreadsheets_values->update(
                $spreadsheetId,
                'Sabianos!A1:F1',
                $headers,
                ['valueInputOption' => 'RAW']
            )
        );
    }

    public function readSabianos(string $spreadsheetId): array
    {
        $this->ensureSabianosSheet($spreadsheetId);

        $values = GoogleRetry::call(fn() =>
            $this->sheets->spreadsheets_values
                ->get($spreadsheetId, 'Sabianos!A2:F10000')
                ->getValues() ?? []
        );

        $out = array_map(fn($r) => [
            'FECHA'  => $r[0] ?? '',
            'SIGNO'  => $r[1] ?? '',
            'GRADO'  => $r[2] ?? '',
            'TITULO' => $r[3] ?? '',
            'TEXTO'  => $r[4] ?? '',
            'IMAGEN' => $r[5] ?? '',
        ], $values);

        // filtrar filas vacías
        return array_values(array_filter($out, function ($row) {
            return trim((string)($row['SIGNO'] ?? '')) !== '' || trim((string)($row['GRADO'] ?? '')) !== '';
        }));
    }

    public function appendSabiano(string $spreadsheetId, array $row): void
    {
        $this->ensureSabianosSheet($spreadsheetId);

        $values = [[
            $row['FECHA']  ?? '',
            $row['SIGNO']  ?? '',
            $row['GRADO']  ?? '',
            $row['TITULO'] ?? '',
            $row['TEXTO']  ?? '',
            $row['IMAGEN'] ?? '',
        ]];

        $body = new ValueRange(['values' => $values]);

        GoogleRetry::call(fn() =>
            $this->sheets->spreadsheets_values->append(
                $spreadsheetId,
                'Sabianos!A1:F1',
                $body,
                ['valueInputOption' => 'RAW', 'insertDataOption' => 'INSERT_ROWS']
            )
        );
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
