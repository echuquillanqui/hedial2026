<?php

namespace Tests\Unit;

use App\Services\DatabaseBackupService;
use Illuminate\Database\SQLiteConnection;
use PDO;
use PHPUnit\Framework\TestCase;

class DatabaseBackupServiceTest extends TestCase
{
    public function test_it_exports_sqlite_schema_data_and_null_values(): void
    {
        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('La extensión pdo_sqlite no está disponible.');
        }

        $pdo = new PDO('sqlite::memory:');
        $connection = new SQLiteConnection($pdo);
        $connection->statement('CREATE TABLE patients (id INTEGER PRIMARY KEY, name TEXT NOT NULL, note TEXT NULL)');
        $connection->table('patients')->insert([
            'id' => 1,
            'name' => "O'Connor",
            'note' => null,
        ]);

        $output = fopen('php://temp', 'w+b');
        (new DatabaseBackupService($connection))->writeSqlDump($output);
        rewind($output);
        $dump = stream_get_contents($output);
        fclose($output);

        $this->assertStringContainsString('CREATE TABLE patients', $dump);
        $this->assertStringContainsString('INSERT INTO "patients" VALUES (\'1\', \'O\'\'Connor\', NULL);', $dump);
        $this->assertStringContainsString('COMMIT;', $dump);
    }
}
