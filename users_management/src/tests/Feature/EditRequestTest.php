<?php

namespace Tests\Feature;

use App\Filament\Resources\RequestResource\Pages\EditRequest;
use App\Models\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Livewire\Livewire;
use Tests\TestCase;

class EditRequestTest extends TestCase
{
    use RefreshDatabase, WithoutMiddleware;

    public function test_can_retrieve_data()
    {
        $request = Request::factory()->create();

        Livewire::test(EditRequest::class, [
            'record' => $request->getRouteKey(),
        ])
            ->assertFormSet([
                'submit_username' => $request->submit_username,
                'identity' => $request->identity,
                'email' => $request->email,
            ]);
    }

    public function test_save()
    {
        $request = Request::factory()->create();
        $newData = Request::factory()->make();

        Livewire::test(EditRequest::class, [
            'record' => $request->getRouteKey(),
        ])
            ->fillForm([
                'submit_username' => $newData->submit_username,
                'identity' => $newData->identity,
                'first_name' => $newData->first_name,
                'last_name' => $newData->last_name,
                'phone' => $newData->phone,
                'email' => $newData->email,
                'unit' => $newData->unit,
                'sub' => $newData->sub,
                'authentication_type' => $newData->authentication_type,
                'service_type' => $newData->service_type,
                'validity' => $newData->validity,
                'description' => $newData->description,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('requests', [
            'identity' => $newData->identity,
        ]);
        $this->assertDatabaseMissing('requests', [
            'identity' => $request->identity,
        ]);
    }
}
