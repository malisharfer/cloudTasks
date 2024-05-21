<?php

namespace Tests\Feature;

use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\User;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Request as RequestFacade;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class UserResourceTest extends TestCase
{
    use RefreshDatabase, WithoutMiddleware;

    public function test_returns_true_for_should_register_navigation_when_user_is_admin()
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $this->actingAs($admin);
        $this->assertTrue(UserResource::shouldRegisterNavigation());
    }

    public function test_returns_false_for_should_register_navigation_when_user_is_not_admin()
    {
        $user = User::factory()->create(['role' => 'User']);
        $this->actingAs($user);
        $this->assertFalse(UserResource::shouldRegisterNavigation());
    }

    public function test_returns_user_from_azure()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->assertEquals($user, UserResource::getUserFromAzure());
    }

    public function test_should_return_model_label()
    {
        $model_label = UserResource::getModelLabel();
        $this->assertIsString($model_label);
    }

    public function test_should_return_plural_model_label()
    {
        $plural_model_label = UserResource::getPluralModelLabel();
        $this->assertIsString($plural_model_label);
    }

    public function test_table_function_returns_table_object()
    {
        $tableMock = $this->createMock(Table::class);
        $result = UserResource::table($tableMock);
        $this->assertInstanceOf(Table::class, $result);
        $this->assertEquals($tableMock, $result);
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
            ->assertCanRenderTableColumn('phone')
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

    public function test_can_search_users_by_phone()
    {
        $users = User::factory()->count(3)->create();
        $phone_to_find = $users->first()->phone;

        Livewire::test(ListUsers::class)
            ->searchTable($phone_to_find)
            ->assertCanSeeTableRecords($users->where('phone', $phone_to_find))
            ->assertCanNotSeeTableRecords($users->where('phone', '!=', $phone_to_find));
    }

    public function test_can_search_users_by_role()
    {
        $users = User::factory()->count(3)->create();
        $role_to_find = $users->first()->role;

        Livewire::test(ListUsers::class)
            ->searchTable($role_to_find)
            ->assertCanSeeTableRecords($users->where('role', $role_to_find))
            ->assertCanNotSeeTableRecords($users->where('role', '!=', $role_to_find));
    }

    public function test_table_returns_table_with_default_columns()
    {
        $tableMock = $this->createMock(Table::class);

        $requestMock = Mockery::mock(Request::class);
        $requestMock->shouldReceive('input')->with('viewType', 'Table')->andReturn('Table');
        $requestMock->shouldIgnoreMissing();
        RequestFacade::swap($requestMock);

        $expectedColumns = [
            TextColumn::make('name')->label(__('name'))->searchable()->sortable(),
            TextColumn::make('phone')->label(__('phone'))->searchable(),
            TextColumn::make('role')->label(__('role'))->searchable()->sortable(),
            TextColumn::make('email')->label(__('email'))->copyable()->copyMessage('Email address copied')->copyMessageDuration(1500)->searchable(),
        ];

        $tableMock->expects($this->once())->method('striped')->willReturn($tableMock);
        $tableMock->expects($this->once())->method('columns')->with($expectedColumns)->willReturn($tableMock);
        $result = UserResource::table($tableMock);
        $this->assertInstanceOf(Table::class, $result);
    }

    public function test_table_returns_table_with_card_view_columns_when_view_type_is_card()
    {
        $tableMock = $this->createMock(Table::class);

        $requestMock = Mockery::mock(Request::class);
        $requestMock->shouldReceive('input')->with('viewType', 'Table')->andReturn('Card');
        $requestMock->shouldIgnoreMissing();
        RequestFacade::swap($requestMock);

        $expectedColumns = [
            TextColumn::make('name')->label(__('name'))->searchable()->sortable(),
            TextColumn::make('phone')->label(__('phone'))->searchable(),
            TextColumn::make('role')->label(__('role'))->searchable()->sortable(),
            TextColumn::make('email')->label(__('email'))->copyable()->copyMessage('Email address copied')->copyMessageDuration(1500)->searchable(),
            Split::make([]),
        ];

        $tableMock->expects($this->once())->method('contentGrid')->with(['md' => 2, 'xl' => 3])->willReturn($tableMock);
        $tableMock->expects($this->once())->method('columns')->with($expectedColumns)->willReturn($tableMock);

        $result = UserResource::table($tableMock);
        $this->assertInstanceOf(Table::class, $result);
    }
}
