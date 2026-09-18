<?php

namespace App\Http\Controllers;

use App\Services\DatabaseBackupService;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DatabaseBackupController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:database.backup.export');
    }

    public function store(): StreamedResponse
    {
        $filename = 'hemodial-respaldo-'.now()->format('Y-m-d_His').'.sql';

        return response()->streamDownload(function () {
            $output = fopen('php://output', 'wb');

            (new DatabaseBackupService(DB::connection()))->writeSqlDump($output);

            fclose($output);
        }, $filename, [
            'Content-Type' => 'application/sql; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
