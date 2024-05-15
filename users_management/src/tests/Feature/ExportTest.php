<?php

use App\Exports\ExportRequests;
use App\Exports\ExportUser;
use App\Filament\Resources\RequestResource\Pages\ListRequests;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ExportTest extends TestCase
{
    use RefreshDatabase,WithoutMiddleware;

    public function testExportRequest()
    {
        Excel::fake();

        Livewire::test(ListRequests::class)
            ->callAction(('export'));

        Excel::assertDownloaded('Requests.xlsx', function (ExportRequests $export) {
            return true;
        });
    }

    public function testExportUser()
    {
        Excel::fake();

        Livewire::test(ListUsers::class)
            ->callAction(('export'));

        Excel::assertDownloaded('Users.xlsx', function (ExportUser $export) {
            return true;
        });
    }
}
