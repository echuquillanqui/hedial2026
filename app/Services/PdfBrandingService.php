<?php

namespace App\Services;

use App\Models\FuaConfiguration;

class PdfBrandingService
{
    public function data(): array
    {
        $configuration = FuaConfiguration::global();
        $candidates = array_filter([
            $configuration->logo_path ? storage_path('app/public/'.$configuration->logo_path) : null,
            public_path('logo/logo_03.jpeg'),
            public_path('logo/logo-fissal.png'),
        ]);

        $logoData = null;
        foreach ($candidates as $path) {
            if (is_file($path)) {
                $mime = mime_content_type($path) ?: 'image/jpeg';
                $logoData = 'data:'.$mime.';base64,'.base64_encode(file_get_contents($path));
                break;
            }
        }

        return compact('configuration', 'logoData');
    }
}
