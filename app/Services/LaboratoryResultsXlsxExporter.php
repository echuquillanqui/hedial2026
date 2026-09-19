<?php

namespace App\Services;

use Illuminate\Support\Collection;
use RuntimeException;

class LaboratoryResultsXlsxExporter
{
    public function export(Collection $orders): string
    {
        $path = tempnam(sys_get_temp_dir(), 'laboratory-results-');

        if ($path === false) {
            throw new RuntimeException('No se pudo crear el archivo temporal para la exportación.');
        }

        $archive = $this->createZipArchive([
            '[Content_Types].xml' => $this->contentTypes(),
            '_rels/.rels' => $this->rootRelationships(),
            'xl/workbook.xml' => $this->workbook(),
            'xl/_rels/workbook.xml.rels' => $this->workbookRelationships(),
            'xl/styles.xml' => $this->styles(),
            'xl/worksheets/sheet1.xml' => $this->worksheet($orders),
        ]);

        if (file_put_contents($path, $archive) === false) {
            @unlink($path);
            throw new RuntimeException('No se pudo escribir el archivo Excel.');
        }

        return $path;
    }

    /**
     * Build the small ZIP container required by XLSX without relying on ext-zip.
     *
     * The entries are stored without compression. This keeps the export available
     * on Windows installations where the optional ZipArchive extension is disabled.
     *
     * @param  array<string, string>  $files
     */
    private function createZipArchive(array $files): string
    {
        $contents = '';
        $directory = '';
        $offset = 0;
        [$dosTime, $dosDate] = $this->dosTimestamp();

        foreach ($files as $name => $data) {
            $nameLength = strlen($name);
            $dataLength = strlen($data);
            $checksum = crc32($data);

            $localHeader = pack(
                'VvvvvvVVVvv',
                0x04034b50,
                20,
                0,
                0,
                $dosTime,
                $dosDate,
                $checksum,
                $dataLength,
                $dataLength,
                $nameLength,
                0
            );
            $contents .= $localHeader.$name.$data;

            $directory .= pack(
                'VvvvvvvVVVvvvvvVV',
                0x02014b50,
                20,
                20,
                0,
                0,
                $dosTime,
                $dosDate,
                $checksum,
                $dataLength,
                $dataLength,
                $nameLength,
                0,
                0,
                0,
                0,
                0,
                $offset
            ).$name;

            $offset = strlen($contents);
        }

        $fileCount = count($files);
        $endRecord = pack(
            'VvvvvVVv',
            0x06054b50,
            0,
            0,
            $fileCount,
            $fileCount,
            strlen($directory),
            strlen($contents),
            0
        );

        return $contents.$directory.$endRecord;
    }

    /** @return array{int, int} */
    private function dosTimestamp(): array
    {
        $year = max((int) date('Y'), 1980);
        $time = ((int) date('H') << 11) | ((int) date('i') << 5) | (int) (date('s') / 2);
        $date = (($year - 1980) << 9) | ((int) date('n') << 5) | (int) date('j');

        return [$time, $date];
    }

    private function worksheet(Collection $orders): string
    {
        $testNames = $orders
            ->flatMap(fn ($order) => $order->items->pluck('test.name'))
            ->filter()
            ->unique()
            ->values();

        $rows = [[
            'NOMBRES Y APELLIDOS',
            'DNI',
            'FECHA',
            ...$testNames->all(),
        ]];

        foreach ($orders as $order) {
            $resultsByTest = $order->items
                ->filter(fn ($item) => $item->test?->name)
                ->keyBy(fn ($item) => $item->test->name);

            $rows[] = [
                $order->patient_name,
                $order->patient?->dni,
                ($order->sampled_at ?? $order->created_at)->format('d/m/Y'),
                ...$testNames->map(fn ($name) => $resultsByTest->get($name)?->result_value)->all(),
            ];
        }

        $xmlRows = '';
        foreach ($rows as $rowIndex => $row) {
            $cells = '';
            foreach ($row as $columnIndex => $value) {
                $reference = $this->columnName($columnIndex + 1).($rowIndex + 1);
                $style = $rowIndex === 0 ? ' s="1"' : '';
                $escapedValue = htmlspecialchars((string) ($value ?? ''), ENT_XML1 | ENT_QUOTES, 'UTF-8');
                $cells .= "<c r=\"{$reference}\" t=\"inlineStr\"{$style}><is><t xml:space=\"preserve\">{$escapedValue}</t></is></c>";
            }
            $xmlRows .= '<row r="'.($rowIndex + 1).'">'.$cells.'</row>';
        }

        $lastColumn = $this->columnName(count($rows[0]));

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            .'<cols><col min="1" max="1" width="34" customWidth="1"/><col min="2" max="3" width="18" customWidth="1"/>'
            .(count($rows[0]) > 3 ? '<col min="4" max="'.count($rows[0]).'" width="22" customWidth="1"/>' : '')
            .'</cols><sheetData>'.$xmlRows.'</sheetData><autoFilter ref="A1:'.$lastColumn.count($rows).'"/></worksheet>';
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
        return '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>';
    }

    private function rootRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>';
    }

    private function workbook(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Resultados" sheetId="1" r:id="rId1"/></sheets></workbook>';
    }

    private function workbookRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
    }

    private function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><color rgb="FFFFFFFF"/><sz val="11"/><name val="Calibri"/></font></fonts>'
            .'<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF087F5B"/><bgColor indexed="64"/></patternFill></fill></fills>'
            .'<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/></cellXfs>'
            .'</styleSheet>';
    }
}
