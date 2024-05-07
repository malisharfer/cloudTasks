<?php

namespace App\Filament\Resources\RequestResource\Pages;

use App\Models\Request;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;

class ExportRequests
{
    use Exportable;

    public function __invoke(HttpRequest $request)
    {
        $data = Request::all(); 
                
        return Excel::download(new class($data) implements FromCollection {
            private $data;

            public function __construct($data)
            {
                $this->data = $data;
            }

            public function collection()
            {
                return collect($this->data);
            }
        }, 'requests.xlsx');
    }
}
