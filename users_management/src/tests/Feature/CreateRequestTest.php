<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Filament\Resources\RequestResource\Pages\CreateRequest;
use App\Filament\Resources\RequestResource;
use App\Models\Request;
use Livewire\Livewire;
use Tests\TestCase;

class CreateRequestTest extends TestCase
{
    use RefreshDatabase, WithoutMiddleware;

    public function test_render_create_page() {
        $this->get(RequestResource::getUrl('create'))->assertSuccessful();
    }

    public function test_create_request_form() {
        $request = Request::factory()->raw();
        
        Livewire::test(CreateRequest::class)
            ->fillForm($request)
            ->call('create')
            ->assertHasNoFormErrors();
        $this->assertDatabaseCount('requests', 1);

        Livewire::test(CreateRequest::class)
            ->fillForm($request)
            ->call('create')
            ->assertHasFormErrors(['identity' => 'unique']);
    }
    
    public function test_create_request_form_with_invalid_identity() {
        $invalid_identity = '123456789';
        $request = Request::factory()->raw(['identity' => $invalid_identity]);

        Livewire::test(CreateRequest::class)
            ->fillForm($request)
            ->call('create')
            ->assertHasFormErrors();
    }

    public function test_create_request_form_with_null_identity() {
        $null_identity = null;
        $request = Request::factory()->raw(['identity' => $null_identity]);

        Livewire::test(CreateRequest::class)
            ->fillForm($request)
            ->call('create')
            ->assertHasFormErrors(['identity' => 'required']);
    }

    public function test_create_request() {
        $request1 = Request::factory()->create();
        $this->assertModelExists($request1);

        $duplicate_identity = $request1->identity;
        try {
            $request2 = Request::factory()->create(['identity' => $duplicate_identity]);
        } 
        catch (\Exception $e) {
            $this->assertTrue(str_contains($e, 'SQLSTATE[23000]'));
        }
    }
}
