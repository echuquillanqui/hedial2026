<?php

namespace App\Services;

use App\Models\DailyNursingAnnex;
use Illuminate\Support\Collection;
use RuntimeException;

class NursingAnnexXlsxExporter
{
    public function discard(Collection $rows, Collection $codes, Collection $codeCounts, \Carbon\Carbon $month, string $title, array $filters): string
    {
        $headings = ['N.°', 'PACIENTE', 'SECUENCIA'];
        for ($day = 1; $day <= $month->daysInMonth; $day++) {
            $headings[] = (string) $day;
        }
        for ($code = 1; $code <= 5; $code++) {
            $headings[] = "TOTAL CÓDIGO N.° {$code}";
        }

        $data = [
            [$title],
            ['MES', mb_strtoupper($month->locale('es')->translatedFormat('F')), 'AÑO', (string) $month->year],
            ['FILTROS', 'Turno '.($filters['shift'] ?: 'todos'), 'Módulo '.($filters['module'] ?: 'todos'), 'Secuencia '.($filters['sequence'] ?: 'todas')],
            [],
            $headings,
        ];
        foreach ($rows as $index => $row) {
            $line = [$index + 1, $row['patient']->full_name, $row['sequence']];
            for ($day = 1; $day <= $month->daysInMonth; $day++) {
                $line[] = $row['days']->get($day);
            }
            for ($code = 1; $code <= 5; $code++) {
                $line[] = $row['totals']->get($code) ?: null;
            }
            $data[] = $line;
        }

        $data[] = [];
        $data[] = ['N.° DE CÓDIGO', 'PRODUCTO / LOTE', 'CANTIDAD'];
        for ($code = 1; $code <= 5; $code++) {
            $name = $codes->search($code);
            $data[] = [$code, $name ?: null, $name ? $codeCounts->get($name) : null];
        }

        return $this->export($data, 'Anexo 11', [5, count($data) - 5]);
    }

    public function care(DailyNursingAnnex $annex): string
    {
        $data = [
            ['ANEXO N.° 12 - REGISTRO DIARIO DE ATENCIONES DE ENFERMERÍA'],
            ['FECHA', $annex->work_date->format('d/m/Y'), 'FRECUENCIA', $annex->frequency, 'MÓDULO', $annex->module],
            [],
            ['PROCEDIMIENTO', 'TIPO / DETALLE', 'TURNO 1', 'TURNO 2', 'TURNO 3', 'TURNO 4', 'TOTAL', 'OBSERVACIONES'],
        ];
        foreach (DailyNursingAnnexService::ROWS as $key => [$procedure, $detail]) {
            $data[] = [$procedure, $detail,
                data_get($annex->values, "$key.shifts.1", 0), data_get($annex->values, "$key.shifts.2", 0),
                data_get($annex->values, "$key.shifts.3", 0), data_get($annex->values, "$key.shifts.4", 0),
                data_get($annex->values, "$key.quantity", 0), data_get($annex->values, "$key.observations"),
            ];
        }

        return $this->export($data, 'Anexo 12', [4]);
    }

    /** @param array<int, array<int, mixed>> $rows */
    private function export(array $rows, string $sheetName, array $headingRows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'nursing-annex-');
        if ($path === false) {
            throw new RuntimeException('No se pudo crear el archivo temporal para la exportación.');
        }

        $files = [
            '[Content_Types].xml' => $this->contentTypes(),
            '_rels/.rels' => $this->rootRelationships(),
            'xl/workbook.xml' => $this->workbook($sheetName),
            'xl/_rels/workbook.xml.rels' => $this->workbookRelationships(),
            'xl/styles.xml' => $this->styles(),
            'xl/worksheets/sheet1.xml' => $this->worksheet($rows, $headingRows),
        ];
        if (file_put_contents($path, $this->zip($files)) === false) {
            @unlink($path);
            throw new RuntimeException('No se pudo escribir el archivo Excel.');
        }

        return $path;
    }

    private function worksheet(array $rows, array $headingRows): string
    {
        $xml = '';
        foreach ($rows as $rowIndex => $row) {
            $cells = '';
            foreach ($row as $columnIndex => $value) {
                $reference = $this->columnName($columnIndex + 1).($rowIndex + 1);
                $style = in_array($rowIndex + 1, $headingRows, true) ? ' s="1"' : '';
                $value = htmlspecialchars((string) ($value ?? ''), ENT_XML1 | ENT_QUOTES, 'UTF-8');
                $cells .= "<c r=\"{$reference}\" t=\"inlineStr\"{$style}><is><t xml:space=\"preserve\">{$value}</t></is></c>";
            }
            $xml .= '<row r="'.($rowIndex + 1).'">'.$cells.'</row>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetViews><sheetView workbookViewId="0"/></sheetViews>'
            .'<cols><col min="1" max="1" width="32" customWidth="1"/><col min="2" max="2" width="36" customWidth="1"/><col min="3" max="50" width="14" customWidth="1"/></cols>'
            .'<sheetData>'.$xml.'</sheetData></worksheet>';
    }

    /** @param array<string, string> $files */
    private function zip(array $files): string
    {
        $contents = $directory = '';
        $offset = 0;
        $year = max((int) date('Y'), 1980);
        $time = ((int) date('H') << 11) | ((int) date('i') << 5) | (int) (date('s') / 2);
        $date = (($year - 1980) << 9) | ((int) date('n') << 5) | (int) date('j');
        foreach ($files as $name => $data) {
            $length = strlen($data);
            $nameLength = strlen($name);
            $checksum = crc32($data);
            $contents .= pack('VvvvvvVVVvv', 0x04034b50, 20, 0, 0, $time, $date, $checksum, $length, $length, $nameLength, 0).$name.$data;
            $directory .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0, 0, $time, $date, $checksum, $length, $length, $nameLength, 0, 0, 0, 0, 0, $offset).$name;
            $offset = strlen($contents);
        }
        $count = count($files);

        return $contents.$directory.pack('VvvvvVVv', 0x06054b50, 0, 0, $count, $count, strlen($directory), strlen($contents), 0);
    }

    private function columnName(int $index): string
    {
        $name = '';
        while ($index > 0) {
            $index--;
            $name = chr(65 + ($index % 26)).$name;
            $index = intdiv($index, 26);
        }

        return $name;
    }

    private function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>';
    }

    private function rootRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>';
    }

    private function workbook(string $sheetName): string
    {
        $sheetName = htmlspecialchars($sheetName, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="'.$sheetName.'" sheetId="1" r:id="rId1"/></sheets></workbook>';
    }

    private function workbookRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
    }

    private function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><color rgb="FFFFFFFF"/><sz val="11"/><name val="Calibri"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF087F5B"/></patternFill></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/></cellXfs></styleSheet>';
    }
}
