<?php

namespace Tests\Feature;

use App\Enums\Users\Role;
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

        $admin_user = User::factory()->create(['role' => Role::Admin]);
        $this->mock(UserResource::class, function ($mock) use ($admin_user) {
            $mock->shouldReceive('getUserFromAzure')->andReturn($admin_user);
        });
        $this->actingAs($admin_user);
        $this->get(UserResource::getUrl('index'))->assertSuccessful();
    }

    public function test_denies_non_admin_users()
    {
        $non_admin_user = User::factory()->create(['role' => Role::User]);
        $this->mock(UserResource::class, function ($mock) use ($non_admin_user) {
            $mock->shouldReceive('getUserFromAzure')->andReturn($non_admin_user);
        });
        $this->actingAs($non_admin_user);
        $this->get(UserResource::getUrl('index'))->assertUnauthorized();
    }

    public function test_can_list_users()
    {
        $users = User::factory()->count(5)->create();
        Livewire::test(ListUsers::class)
            ->assertCanSeeTableRecords($users);
    }

    public function test_can_render_user_table()
    {
        Livewire::test(ListUsers::class)
            ->assertSuccessful()
            ->assertCanRenderTableColumn('name')
            ->assertCanRenderTableColumn('email')
            ->assertCanRenderTableColumn('role');
    }

    public function test_can_sort_users_by_name()
    {
        $users = User::factory()->count(3)->create();
        Livewire::test(ListUsers::class)
            ->sortTable('name')
            ->assertCanSeeTableRecords($users->sortBy('name'), inOrder: true);
    }

    public function test_can_sort_users_by_role()
    {
        $users = User::factory()->count(3)->create();
        Livewire::test(ListUsers::class)
            ->sortTable('role')
            ->assertCanSeeTableRecords($users->sortBy('role'), inOrder: true);
    }

    public function test_can_search_users_by_name()
    {
        $users = User::factory()->count(3)->create();
        $user_to_find = $users->first()->name;
        Livewire::test(ListUsers::class)
            ->searchTable($user_to_find)
            ->assertCanSeeTableRecords($users->where('name', $user_to_find))
            ->assertCanNotSeeTableRecords($users->where('name', '!=', $user_to_find));
    }

    public function test_returns_correct_header_actions()
    {
        $user = User::factory()->create();
        $headerActions = Livewire::test(ListUsers::class)->call('getHeaderActions');

        $expectedLabel = __('change view');
        $expectedViewType = (request()->input('viewType', 'Table') === 'Table') ? 'Card' : 'Table';
        $expectedUrl = url()->current().'?viewType='.$expectedViewType;

        $headerActions->assertSee($expectedLabel)
            ->assertSee($expectedUrl);
    }
}
