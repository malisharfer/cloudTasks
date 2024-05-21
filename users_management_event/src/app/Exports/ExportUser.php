<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;

class ExportUser implements FromCollection
{
    public function collection()
    {
        return collect([
            ['ID', 'Name', 'Role', 'Email', 'Created_date', 'Approval_date'],
        ])->concat(User::select('id', 'name', 'role', 'email', 'created_at', 'updated_at')->get());
    }
}
