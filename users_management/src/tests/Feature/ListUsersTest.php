<?php

namespace Tests\Feature;

use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Filament\Resources\UserResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;
use Livewire\Livewire;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Filament\Actions\Action;
use Mockery\MockInterface;
use Mockery;
use App\Services\GetUsers;
use App\Enums\Users\Role;



class ListUsersTest extends TestCase
{
    use RefreshDatabase, WithoutMiddleware;

    public function test_allows_admin_users_to_access() 
    {
        $admin_user = User::factory()->create(["role" => Role::Admin]);
        $this->actingAs($admin_user);
        $this->get(UserResource::getUrl('index'))->assertSuccessful();
    }

    public function test_denies_non_admin_users()
    {
        $non_admin_user = User::factory()->create(["role" => Role::User]);  
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
        $expectedUrl = url()->current() . '?viewType=' . $expectedViewType;
    
        $headerActions->assertSee($expectedLabel)
                      ->assertSee($expectedUrl);
    }

    public function test_can_switch_between_table_and_card_views()
    {
        $response = $this->get(UserResource::getUrl('index'));
        $response->assertStatus(200);
        $this->assertEquals(null, request()->input('viewType'));

        $response = $this->get(UserResource::getUrl('index') . '?viewType=Card');
        $response->assertStatus(200);
        $this->assertEquals('Card', request()->input('viewType'));

        $response = $this->get(UserResource::getUrl('index') . '?viewType=Table');
        $response->assertStatus(200);
        $this->assertEquals('Table', request()->input('viewType'));
    }
}