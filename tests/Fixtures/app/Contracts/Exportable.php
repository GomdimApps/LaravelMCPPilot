<?php

namespace App\Contracts;

interface Exportable
{
    public function toExportArray(): array;
}
