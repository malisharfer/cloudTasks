<?php

namespace Tests\Feature;

use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Livewire\Livewire;
use Tests\TestCase;

class ListUsersTest extends TestCase
{
    use RefreshDatabase, WithoutMiddleware;

    public function test_allows_admin_users_to_access()
    {
        $admin_user = User::factory()->create(['role' => 'Admin']);
        $this->mock(UserResource::class, function ($mock) use ($admin_user) {
            $mock->shouldReceive('getUserFromAzure')->andReturn($admin_user);
        });
        $this->actingAs($admin_user);
        $this->get(UserResource::getUrl('index'))->assertSuccessful();
    }

    public function test_denies_non_admin_users()
    {
        $non_admin_user = User::factory()->create(['role' => 'User']);
        $this->mock(UserResource::class, function ($mock) use ($non_admin_user) {
            $mock->shouldReceive('getUserFromAzure')->andReturn($non_admin_user);
        });
        $this->actingAs($non_admin_user);
        $this->get(UserResource::getUrl('index'))->assertUnauthorized();
    }

    public function test_returns_correct_header_actions()
    {
        $headerActions = Livewire::test(ListUsers::class)->call('getHeaderActions');

        $expectedLabel = __('change view');
        $expectedViewType = (request()->input('viewType', 'Table') === 'Table') ? 'Card' : 'Table';
        $expectedUrl = url()->current().'?viewType='.$expectedViewType;

        $headerActions->assertSee($expectedLabel)
            ->assertSee($expectedUrl);
    }

    public function test_can_switch_between_table_and_card_views()
    {
        $response = $this->get(UserResource::getUrl('index'));
        $response->assertStatus(200);
        $this->assertEquals(null, request()->input('viewType'));

        $response = $this->get(UserResource::getUrl('index').'?viewType=Card');
        $response->assertStatus(200);
        $this->assertEquals('Card', request()->input('viewType'));

        $response = $this->get(UserResource::getUrl('index').'?viewType=Table');
        $response->assertStatus(200);
        $this->assertEquals('Table', request()->input('viewType'));
    }
}
