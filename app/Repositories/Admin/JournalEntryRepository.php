<?php

namespace App\Repositories\Admin;

use App\Models\JournalEntry;

class JournalEntryRepository extends TenantScopedRepository
{
    public function __construct(JournalEntry $model)
    {
        $this->model = $model;
    }
}
