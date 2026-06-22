<?php

declare(strict_types=1);

namespace App\Traits;

trait HasTimestamps
{
    protected bool $useTimestamps = true;
    protected string $createdAtColumn = 'created_at';
    protected string $updatedAtColumn = 'updated_at';

    protected function addTimestamps(array &$data, bool $isUpdate = false): void
    {
        $now = date('Y-m-d H:i:s');
        if (!$isUpdate) $data[$this->createdAtColumn] ??= $now;
        $data[$this->updatedAtColumn] ??= $now;
    }
}
