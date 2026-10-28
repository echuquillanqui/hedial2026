<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use RuntimeException;

class LaboratoryEvolutionXlsxExporter
{
    public function export(Collection $patients, Collection $tests, array $months): string
    {
        $sheets = [];
        foreach ($patients as $patient) {
            $sheets[] = $this->worksheet($patient, $tests, $months);
        }
        if ($sheets === []) {
            $sheets[] = $this->emptyWorksheet($months);
        }

        $files = [
            '[Content_Types].xml' => $this->contentTypes(count($sheets)),
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>',
            'xl/workbook.xml' => $this->workbook($patients),
            'xl/_rels/workbook.xml.rels' => $this->relationships(count($sheets)),
            'xl/styles.xml' => $this->styles(),
        ];
        foreach ($sheets as $index => $sheet) {
            $files['xl/worksheets/sheet'.($index + 1).'.xml'] = $sheet;
        }

        $path = tempnam(sys_get_temp_dir(), 'lab-evolution-');
        if ($path === false || file_put_contents($path, $this->zip($files)) === false) {
            throw new RuntimeException('No se pudo crear el Excel de evolución.');
        }

        return $path;
    }

    private function worksheet($patient, Collection $tests, array $months): string
    {
        $data = [];
        foreach ($patient->laboratoryOrders as $order) {
            $date = $order->sampled_at ?? $order->created_at;
            if (! $date) continue;
            $key = $date->format('Y-m');
            foreach ($order->items as $item) {
                if ($item->result_value !== null) $data[$item->test_id][$key] = $item->result_value;
            }
        }
        $monthLabels = array_map(fn (CarbonImmutable $month) => $this->monthLabel($month), $months);
        $patientCode = $this->patientCode((int) $patient->id);
        $rows = [
            ['PACIENTE:', $patientCode],
            ['EXÁMENES', ...$monthLabels],
        ];
        $frequencyLabels = ['M' => 'MENSUAL', 'B' => 'BIMESTRAL', 'T' => 'TRIMESTRAL', 'S' => 'SEMESTRAL'];
        $lastFrequency = null;
        foreach ($tests as $test) {
            if ($test->frequency !== $lastFrequency) {
                $rows[] = [$frequencyLabels[$test->frequency] ?? 'OTROS'];
                $lastFrequency = $test->frequency;
            }
            $rows[] = [$test->name.($test->unit ? ' ('.$test->unit.')' : ''), ...array_map(fn ($month) => $data[$test->id][$month->format('Y-m')] ?? '', $months)];
        }

        return $this->rowsXml($rows, count($months) + 1);
    }

    private function emptyWorksheet(array $months): string
    {
        return $this->rowsXml([['SIN PACIENTES PARA LA SECUENCIA Y PERIODO SELECCIONADOS'], ['EXÁMENES', ...array_map(fn ($month) => $this->monthLabel($month), $months)]], count($months) + 1);
    }

    private function rowsXml(array $rows, int $columns): string
    {
        $xml = '';
        foreach ($rows as $r => $row) {
            $cells = '';
            foreach ($row as $c => $value) {
                $ref = $this->columnName($c + 1).($r + 1);
                $style = ($r < 2 || count($row) === 1) ? ' s="1"' : '';
                $safe = htmlspecialchars((string) ($value ?? ''), ENT_XML1 | ENT_QUOTES, 'UTF-8');
                $cells .= '<c r="'.$ref.'" t="inlineStr"'.$style.'><is><t xml:space="preserve">'.$safe.'</t></is></c>';
            }
            $xml .= '<row r="'.($r + 1).'">'.$cells.'</row>';
        }
        $last = $this->columnName($columns);
        $monthColumns = $columns > 1 ? '<col min="2" max="'.$columns.'" width="16" customWidth="1"/>' : '';
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetViews><sheetView workbookViewId="0"><pane xSplit="1" ySplit="2" topLeftCell="B3" activePane="bottomRight" state="frozen"/></sheetView></sheetViews><cols><col min="1" max="1" width="38" customWidth="1"/>'.$monthColumns.'</cols><sheetData>'.$xml.'</sheetData><autoFilter ref="A2:'.$last.count($rows).'"/></worksheet>';
    }

    private function workbook(Collection $patients): string
    {
        $names = []; $sheets = '';
        foreach ($patients as $i => $patient) {
            $name = $this->patientCode((int) $patient->id);
            $name = mb_substr(trim($name), 0, 31);
            $base = $name; $suffix = 2;
            while (in_array(mb_strtolower($name), $names, true)) {
                $tail = ' '.$suffix++;
                $name = mb_substr($base, 0, 31 - mb_strlen($tail)).$tail;
            }
            $names[] = mb_strtolower($name);
            $sheets .= '<sheet name="'.htmlspecialchars($name, ENT_XML1 | ENT_QUOTES, 'UTF-8').'" sheetId="'.($i + 1).'" r:id="rId'.($i + 1).'"/>';
        }
        if ($sheets === '') $sheets = '<sheet name="Sin pacientes" sheetId="1" r:id="rId1"/>';
        return '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>'.$sheets.'</sheets></workbook>';
    }

    private function patientCode(int $patientId): string
    {
        return 'COD'.str_pad((string) $patientId, 5, '0', STR_PAD_LEFT);
    }

    private function relationships(int $count): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        for ($i = 1; $i <= max(1, $count); $i++) $xml .= '<Relationship Id="rId'.$i.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$i.'.xml"/>';
        return $xml.'<Relationship Id="rId'.(max(1, $count) + 1).'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
    }

    private function contentTypes(int $count): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        for ($i = 1; $i <= max(1, $count); $i++) $xml .= '<Override PartName="/xl/worksheets/sheet'.$i.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        return $xml.'</Types>';
    }

    private function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><color rgb="FFFFFFFF"/><sz val="11"/><name val="Calibri"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF087F5B"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/></cellXfs></styleSheet>';
    }

    private function zip(array $files): string
    {
        $body = ''; $directory = ''; $offset = 0; $time = (int) date('H') << 11 | (int) date('i') << 5 | (int) (date('s') / 2); $date = (max((int) date('Y'), 1980) - 1980) << 9 | (int) date('n') << 5 | (int) date('j');
        foreach ($files as $name => $contents) {
            $len = strlen($contents); $nlen = strlen($name); $crc = crc32($contents);
            $body .= pack('VvvvvvVVVvv', 0x04034b50, 20, 0, 0, $time, $date, $crc, $len, $len, $nlen, 0).$name.$contents;
            $directory .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0, 0, $time, $date, $crc, $len, $len, $nlen, 0, 0, 0, 0, 0, $offset).$name;
            $offset = strlen($body);
        }
        $count = count($files);
        return $body.$directory.pack('VvvvvVVv', 0x06054b50, 0, 0, $count, $count, strlen($directory), strlen($body), 0);
    }

    private function monthLabel(CarbonImmutable $month): string
    {
        return ['ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SET', 'OCT', 'NOV', 'DIC'][$month->month - 1].'-'.$month->format('y');
    }

    private function columnName(int $index): string
    {
        $name = '';
        while ($index > 0) { $index--; $name = chr(65 + $index % 26).$name; $index = intdiv($index, 26); }
        return $name;
    }
}
