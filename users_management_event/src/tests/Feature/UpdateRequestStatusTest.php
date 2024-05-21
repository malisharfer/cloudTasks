<?php

namespace Tests\Feature;

use App\Enums\Requests\Status;
use App\Filament\Resources\RequestResource\Pages\ListRequests;
use App\Models\Request;
use App\Models\User;
use App\Notifications\Email;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class UpdateRequestStatusTest extends TestCase
{
    use RefreshDatabase, WithoutMiddleware;

    public function test_approval_request()
    {
        $request = Request::factory()->create();

        Notification::fake();

        Livewire::test(ListRequests::class)
            ->callTableAction('approval', $request)
            ->assertNotified();

        Config::set('MAIL_SUFFIX', '@test.com');
        $email = $request->submit_username.'@test.com';
        $user = User::factory()->create(['email' => $email]);

        Notification::assertNotSentTo(
            [$user], Email::class
        );

        $this->assertDatabaseHas('requests', [
            'status' => Status::Approved,
        ]);

        $request->status = Status::Approved;
        Livewire::test(ListRequests::class)
            ->assertTableActionDisabled('approval', $request);
    }
}
