<?php

namespace App\Events;

use App\Models\Thing;

class ThingCreated
{
    public function __construct(public readonly Thing $thing) {}
}
