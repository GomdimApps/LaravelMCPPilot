<?php

namespace App\Services;

use App\Contracts\Exportable;
use App\Models\Thing;

class ThingExportService implements Exportable
{
    public function toExportArray(): array
    {
        return [];
    }

    public function export(Thing $thing): array
    {
        return ['title' => $thing->title];
    }
}
