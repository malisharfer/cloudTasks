<?php

namespace App\Exports;

use App\Models\Request;
use Maatwebsite\Excel\Concerns\FromCollection;

class ExportRequests implements FromCollection
{
    public function collection()
    {
        return collect([
            ['ID', 'Submit_username', 'Identity', 'First_name', 'Last_name', 'Phone', 'Email', 'Unit', 'Sub', 'Authentication_type', 'Service_type', 'Validity', 'Status', 'Description', 'Created_date', 'Approval_date'],
        ])
            ->concat(Request::all());
    }
}
