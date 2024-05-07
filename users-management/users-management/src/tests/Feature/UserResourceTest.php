<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Livewire\Livewire;
use App;
use Mockery;
use App\Models\User;
use App\Services\GetUsers;
use App\Filament\Resources\UserResource;
use Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Request as RequestFacade;
use App\Enums\Users\Role;


class UserResourceTest extends TestCase
{
    use RefreshDatabase, WithoutMiddleware;

    public function test_returns_true_for_should_register_navigation_when_user_is_admin()
    {
        $admin =  User::factory()->create(['role' => Role::Admin]);
        $this->actingAs($admin);
        $this->assertTrue(UserResource::shouldRegisterNavigation());
    }

    public function test_returns_false_for_should_register_navigation_when_user_is_not_admin()
    {
        $non_admin_user = User::factory()->create(["role" => Role::User]);
        
        $this->actingAs($non_admin_user);
        $result = UserResource::shouldRegisterNavigation();
        $this->assertFalse($result);  
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

    public function test_table_returns_table_with_default_columns()
    {
        $tableMock = $this->createMock(Table::class);
      
        $requestMock = Mockery::mock(Request::class);
        $requestMock->shouldReceive('input')->with('viewType', 'Table')->andReturn('Table');
        $requestMock->shouldIgnoreMissing();
        RequestFacade::swap($requestMock);

        $expectedColumns = [
            TextColumn::make('name')->label(__('Name'))->searchable()->sortable(),
            TextColumn::make('role')->label(__('Role'))->searchable()->sortable(),
            TextColumn::make('email')->label(__('Email'))->sortable()->searchable()->copyable()->copyMessage(__('Email address copied'))->copyMessageDuration(1500),
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
            TextColumn::make('name')->label(__('Name'))->searchable()->sortable(),
            TextColumn::make('role')->label(__('Role'))->searchable()->sortable(),
            TextColumn::make('email')->label(__('Email'))->sortable()->searchable()->copyable()->copyMessage(__('Email address copied'))->copyMessageDuration(1500),
            Split::make([]),
        ];
        
        $tableMock->expects($this->once())->method('contentGrid')->with(['md' => 2, 'xl' => 3])->willReturn($tableMock);
        $tableMock->expects($this->once())->method('columns')->with($expectedColumns)->willReturn($tableMock);
        
        $result = UserResource::table($tableMock);
        $this->assertInstanceOf(Table::class, $result);
    }
}
