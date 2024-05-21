<?php

namespace Tests\Feature;

use App\Enums\Requests\Status;
use App\Filament\Resources\RequestResource;
use App\Filament\Resources\RequestResource\Pages\ListRequests;
use App\Models\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Livewire\Livewire;
use Tests\TestCase;

class ListRequestTest extends TestCase
{
    use RefreshDatabase, WithoutMiddleware;

    public function test_render_index_page()
    {
        $this->get(RequestResource::getUrl('index'))->assertSuccessful();
    }

    public function test_display_requests()
    {
        $requests = Request::factory()->count(2)->create();

        Livewire::test(ListRequests::class)
            ->assertCanSeeTableRecords($requests)
            ->assertCountTableRecords(2)
            ->assertCanRenderTableColumn('fullname');
    }

    public function test_sort_requests()
    {
        $requests = Request::factory()->count(2)->create();

        Livewire::test(ListRequests::class)
            ->sortTable('created_at')
            ->assertCanSeeTableRecords($requests->sortBy('created_at'), inOrder: true)
            ->sortTable('created_at', 'desc')
            ->assertCanSeeTableRecords($requests->sortByDesc('created_at'), inOrder: true);
    }

    public function test_search_requests()
    {
        $requests = Request::factory()->count(2)->create();

        $identity = $requests->first()->identity;

        Livewire::test(ListRequests::class)
            ->searchTable($identity)
            ->assertCanSeeTableRecords($requests->where('identity', $identity))
            ->assertCanNotSeeTableRecords($requests->where('identity', '!=', $identity));

        $fullname = $requests->first()->fullname;

        Livewire::test(ListRequests::class)
            ->searchTable($fullname)
            ->assertCanSeeTableRecords($requests->where('first_name', $fullname))
            ->assertCanSeeTableRecords($requests->where('last_name', $fullname));
    }

    public function test_filter_requests()
    {
        $requests = Request::factory()->count(2)->create();

        Livewire::test(ListRequests::class)
            ->assertCanSeeTableRecords($requests)
            ->filterTable('status', Status::New->value)
            ->assertCanSeeTableRecords($requests->where('status', Status::New->value))
            ->assertCanNotSeeTableRecords($requests->where('status', Status::Approved->value));

        $oldRequests = Request::factory()->count(2)->create(['created_at' => now()->subWeek()]);

        Livewire::test(ListRequests::class)
            ->assertCanSeeTableRecords([...$requests, ...$oldRequests])
            ->filterTable('created_at', ['from' => now(), 'until' => null])
            ->assertCanSeeTableRecords($requests)
            ->assertCanNotSeeTableRecords($oldRequests);
    }
}
