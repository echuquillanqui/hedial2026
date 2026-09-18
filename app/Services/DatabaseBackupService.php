<?php

namespace App\Services;

use Illuminate\Database\ConnectionInterface;
use RuntimeException;

class DatabaseBackupService
{
    public function __construct(private readonly ConnectionInterface $connection)
    {
    }

    public function writeSqlDump($output): void
    {
        $driver = $this->connection->getDriverName();

        if (! in_array($driver, ['mysql', 'sqlite'], true)) {
            throw new RuntimeException("La exportación no está disponible para el motor de base de datos {$driver}.");
        }

        $startedTransaction = ! $this->connection->transactionLevel();

        if ($startedTransaction) {
            $this->connection->beginTransaction();
        }

        try {
            $this->write($output, "-- Respaldo de base de datos generado por HEMODIAL\n");
            $this->write($output, '-- Fecha: '.now()->toIso8601String()."\n\n");

            if ($driver === 'mysql') {
                $this->writeMysqlDump($output);

                return;
            }

            $this->writeSqliteDump($output);
        } finally {
            if ($startedTransaction) {
                $this->connection->rollBack();
            }
        }
    }

    private function writeMysqlDump($output): void
    {
        $this->write($output, "SET FOREIGN_KEY_CHECKS=0;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\nSTART TRANSACTION;\n\n");

        $database = $this->connection->getDatabaseName();
        $rows = $this->connection->select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
        $tableKey = 'Tables_in_'.$database;

        foreach ($rows as $row) {
            $values = (array) $row;
            $table = $values[$tableKey] ?? reset($values);
            $quotedTable = '`'.str_replace('`', '``', $table).'`';
            $createRow = (array) $this->connection->selectOne("SHOW CREATE TABLE {$quotedTable}");
            $createSql = $createRow['Create Table'] ?? array_values($createRow)[1];

            $this->write($output, "DROP TABLE IF EXISTS {$quotedTable};\n{$createSql};\n\n");
            $this->writeRows($output, $table, $quotedTable);
        }

        $this->write($output, "COMMIT;\nSET FOREIGN_KEY_CHECKS=1;\n");
    }

    private function writeSqliteDump($output): void
    {
        $this->write($output, "PRAGMA foreign_keys=OFF;\nBEGIN TRANSACTION;\n\n");
        $tables = $this->connection->select(
            "SELECT name, sql FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name"
        );

        foreach ($tables as $tableDefinition) {
            $table = $tableDefinition->name;
            $quotedTable = '"'.str_replace('"', '""', $table).'"';
            $this->write($output, "DROP TABLE IF EXISTS {$quotedTable};\n{$tableDefinition->sql};\n\n");
            $this->writeRows($output, $table, $quotedTable);
        }

        $this->write($output, "COMMIT;\nPRAGMA foreign_keys=ON;\n");
    }

    private function writeRows($output, string $table, string $quotedTable): void
    {
        $this->connection->table($table)->orderByRaw('1')->chunk(250, function ($rows) use ($output, $quotedTable) {
            foreach ($rows as $row) {
                $values = array_map(fn ($value) => $this->sqlValue($value), array_values((array) $row));
                $this->write($output, "INSERT INTO {$quotedTable} VALUES (".implode(', ', $values).");\n");
            }
        });

        $this->write($output, "\n");
    }

    private function sqlValue(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return $this->connection->getPdo()->quote((string) $value);
    }

    private function write($output, string $contents): void
    {
        if (fwrite($output, $contents) === false) {
            throw new RuntimeException('No se pudo escribir el respaldo de la base de datos.');
        }
    }
}
