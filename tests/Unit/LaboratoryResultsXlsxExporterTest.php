<?php

namespace Tests\Unit;

use App\Services\LaboratoryResultsXlsxExporter;
use PHPUnit\Framework\TestCase;

class LaboratoryResultsXlsxExporterTest extends TestCase
{
    public function test_it_creates_a_valid_xlsx_file(): void
    {
        $path = (new LaboratoryResultsXlsxExporter())->export(collect());

        try {
            $contents = file_get_contents($path);

            $this->assertStringStartsWith("PK\x03\x04", $contents);
            $this->assertSame("PK\x05\x06", substr($contents, -22, 4));
            $this->assertSame(6, substr_count($contents, "PK\x03\x04"));
            $this->assertStringContainsString('[Content_Types].xml', $contents);
            $this->assertStringContainsString('xl/worksheets/sheet1.xml', $contents);
            $this->assertStringContainsString('<autoFilter ref="A1:M1"/>', $contents);
        } finally {
            @unlink($path);
        }
    }

    public function test_exporter_does_not_depend_on_the_zip_archive_extension(): void
    {
        $source = file_get_contents(__DIR__.'/../../app/Services/LaboratoryResultsXlsxExporter.php');

        $this->assertStringNotContainsString('new ZipArchive', $source);
        $this->assertStringNotContainsString('use ZipArchive', $source);
    }
}
