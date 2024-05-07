<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Config;
use App\Filament\Resources\RequestResource\Pages\ListRequests;
use App\Filament\Resources\RequestResource;
use App\Notifications\Email;
use App\Models\Request;
use App\Models\User;
use App\Enums\Requests\Status;
use Livewire\Livewire;
use Tests\TestCase;

class ListRequestTest extends TestCase
{
    use RefreshDatabase, WithoutMiddleware;

    public function test_render_index_page() {
        $this->get(RequestResource::getUrl('index'))->assertSuccessful();
    }

    public function test_display_requests() {
        $requests = Request::factory()->count(2)->create();
     
        Livewire::test(ListRequests::class)
            ->assertCanSeeTableRecords($requests)
            ->assertCountTableRecords(2)
            ->assertCanRenderTableColumn('fullname');
    }

    public function test_sort_requests() {
        $requests = Request::factory()->count(2)->create();
     
        Livewire::test(ListRequests::class)
            ->sortTable('created_at')
            ->assertCanSeeTableRecords($requests->sortBy('created_at'), inOrder: true)
            ->sortTable('created_at', 'desc')
            ->assertCanSeeTableRecords($requests->sortByDesc('created_at'), inOrder: true);
    }

    public function test_search_requests() {
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
    
    public function test_filter_requests() {
        $requests = Request::factory()->count(2)->create();
     
        Livewire::test(ListRequests::class)
            ->assertCanSeeTableRecords($requests)
            ->filterTable('status')
            ->assertCanSeeTableRecords($requests->where('status', 'new'))
            ->assertCanNotSeeTableRecords($requests->where('status', 'approve'));
    }

    public function test_approval_request() {
        $request = Request::factory()->create();
        
        Notification::fake();

        Livewire::test(ListRequests::class)
            ->callTableAction(__('approval'), $request)
            ->assertTableActionDisabled(__('approval'))
            ->assertNotified();

        Config::set('MAIL_SUFFIX', '@test.com');
        $email = $request->submit_username . '@test.com';
        $user = User::factory()->create(['email' => $email]);
        
        Notification::assertNotSentTo(
            [$user], Email::class
        );

        $this->assertDatabaseHas('requests', [
            'status' => Status::Approved,
        ]);
    }

    public function test_deny_request() {
        $request = Request::factory()->create();
        
        Notification::fake();
        
        Livewire::test(ListRequests::class)
            ->callTableAction(__('deny'), $request)
            ->assertTableActionDisabled(__('deny'))
            ->assertNotified();

        Config::set('MAIL_SUFFIX', '@test.com');
        $email = $request->submit_username . '@test.com';
        $user = User::factory()->create(['email' => $email]);
        
        Notification::assertNotSentTo(
            [$user], Email::class
        );

        $this->assertDatabaseHas('requests', [
            'status' => Status::Denied,
        ]);
    }
}
