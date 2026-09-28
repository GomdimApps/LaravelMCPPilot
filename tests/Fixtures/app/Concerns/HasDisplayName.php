<?php

namespace App\Concerns;

trait HasDisplayName
{
    public function displayName(): string
    {
        return class_basename(static::class);
    }
}
